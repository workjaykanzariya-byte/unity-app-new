<?php

namespace App\Services\Zoho;

use App\Enums\CircleBillingTerm;
use App\Models\Circle;
use App\Models\CircleZohoAddon;
use App\Support\Zoho\ZohoBillingClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CircleAddonSyncService
{
    public function __construct(
        private readonly CircleAddonCodeGenerator $codeGenerator,
        private readonly CircleAddonPayloadBuilder $payloadBuilder,
        private readonly ZohoBillingClient $client,
    ) {
    }

    public function syncCircle(Circle $circle): array
    {
        Log::info('[CircleAddonSync] start', [
            'circle_id' => $circle->id,
            'circle_name' => $circle->name,
        ]);

        if (! Schema::hasTable('circle_zoho_addons')) {
            Log::warning('[CircleAddonSync] skipped: circle_zoho_addons table missing', [
                'circle_id' => $circle->id,
            ]);

            return ['created' => 0, 'updated' => 0, 'skipped' => 4, 'errors' => 0];
        }

        if (! $this->isPaymentEnabled($circle)) {
            $this->markCircleAddonsInactive($circle);

            Log::info('[CircleAddonSync] skipped: payment disabled', ['circle_id' => $circle->id]);

            return ['created' => 0, 'updated' => 0, 'skipped' => 4, 'errors' => 0];
        }

        $counts = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];

        foreach (CircleBillingTerm::cases() as $term) {
            $amount = $this->resolveAmount($circle, $term);

            Log::info('[CircleAddonSync] syncing term', [
                'circle_id' => $circle->id,
                'billing_term' => $term->value,
                'amount' => $amount,
            ]);

            if ($amount <= 0) {
                $this->markTermInactive($circle, $term);
                $counts['skipped']++;
                continue;
            }

            $code = $this->codeGenerator->generate($circle, $term);
            $payload = $this->payloadBuilder->build($circle, $term, $code, $amount);
            $name = (string) ($payload['name'] ?? '');
            $description = (string) ($payload['description'] ?? '');
            $syncHash = $this->payloadBuilder->syncHash($circle, $term, $amount, $name, $description, true);

            $local = CircleZohoAddon::query()->firstOrNew([
                'circle_id' => $circle->id,
                'billing_term' => $term->value,
            ]);

            $existingCode = (string) ($local->zoho_addon_code ?? $local->addon_code ?? '');

            if ($local->exists && (string) ($local->sync_hash ?? '') === $syncHash && $existingCode === $code && (bool) ($local->is_active ?? true)) {
                Log::info('[CircleAddonSync] no changes, skipped', [
                    'circle_id' => $circle->id,
                    'billing_term' => $term->value,
                    'addon_code' => $code,
                ]);

                $counts['skipped']++;
                continue;
            }

            try {
                $remoteAddon = $this->resolveRemoteAddon($local, $code);
                $action = 'created';

                if ($remoteAddon !== null) {
                    $remoteId = (string) ($remoteAddon['addon_id'] ?? '');
                    if ($remoteId !== '') {
                        $remoteAddon = $this->updateRemoteAddon($remoteId, $payload);
                        $action = 'updated';
                    }
                }

                if ($remoteAddon === null) {
                    $remoteAddon = $this->createRemoteAddon($circle, $term, $payload, $amount, $code);
                    $action = 'created';
                }

                $this->saveLocalAddon($local, $circle, $term, $amount, $payload, $syncHash, $code, $remoteAddon, true);

                $counts[$action]++;

                Log::info('[CircleAddonSync] synced term', [
                    'circle_id' => $circle->id,
                    'billing_term' => $term->value,
                    'addon_code' => $code,
                    'zoho_addon_id' => (string) ($remoteAddon['addon_id'] ?? ''),
                    'path' => $action,
                ]);
            } catch (\Throwable $throwable) {
                $counts['errors']++;

                Log::error('[CircleAddonSync] failed term sync', [
                    'circle_id' => $circle->id,
                    'circle_name' => $circle->name,
                    'billing_term' => $term->value,
                    'amount' => $amount,
                    'addon_code' => $code,
                    'error' => $throwable->getMessage(),
                ]);
            }
        }

        Log::info('[CircleAddonSync] completed', array_merge(['circle_id' => $circle->id], $counts));

        return $counts;
    }

    public function resolveAvailablePlans(Circle $circle): array
    {
        return collect(CircleBillingTerm::cases())
            ->map(function (CircleBillingTerm $term) use ($circle): array {
                $amount = $this->resolveAmount($circle, $term);

                return [
                    'billing_term' => $term->value,
                    'label' => $term->label(),
                    'months' => $term->months(),
                    'amount' => $amount,
                    'available' => $this->isPaymentEnabled($circle) && $amount > 0,
                    'addon_code' => $this->codeGenerator->generate($circle, $term),
                ];
            })
            ->values()
            ->all();
    }

    public function isPaymentEnabled(Circle $circle): bool
    {
        foreach (['circle_payment_enabled', 'payment_enabled', 'is_payment_enabled', 'is_paid', 'paid_enabled'] as $column) {
            if (Schema::hasColumn('circles', $column)) {
                return (bool) data_get($circle, $column, false);
            }
        }

        return false;
    }

    public function resolveAmount(Circle $circle, CircleBillingTerm $term): float
    {
        $candidates = match ($term) {
            CircleBillingTerm::MONTHLY => ['monthly_price', 'monthly_amount', 'price_monthly', 'amount_monthly'],
            CircleBillingTerm::QUARTERLY => ['quarterly_price', 'quarterly_amount', 'price_quarterly', 'amount_quarterly'],
            CircleBillingTerm::HALF_YEARLY => ['half_yearly_price', 'half_yearly_amount', 'price_half_yearly', 'six_month_amount'],
            CircleBillingTerm::YEARLY => ['yearly_price', 'yearly_amount', 'price_yearly', 'annual_amount'],
        };

        foreach ($candidates as $column) {
            if (Schema::hasColumn('circles', $column)) {
                return round((float) data_get($circle, $column, 0), 2);
            }
        }

        return 0;
    }

    private function resolveRemoteAddon(CircleZohoAddon $local, string $code): ?array
    {
        $localAddonId = (string) ($local->zoho_addon_id ?? '');
        if ($localAddonId !== '') {
            try {
                $response = $this->client->request('GET', '/addons/' . $localAddonId);
                $addon = $response['addon'] ?? null;
                if (is_array($addon)) {
                    Log::info('[CircleAddonSync] found existing remote by local addon id', [
                        'zoho_addon_id' => $localAddonId,
                        'addon_code' => $code,
                    ]);
                    return $addon;
                }
            } catch (\Throwable $throwable) {
                Log::warning('[CircleAddonSync] local zoho_addon_id not found remotely', [
                    'zoho_addon_id' => $localAddonId,
                    'addon_code' => $code,
                    'error' => $throwable->getMessage(),
                ]);
            }
        }

        return $this->findRemoteAddonByCode($code);
    }

    private function createRemoteAddon(Circle $circle, CircleBillingTerm $term, array $payload, float $amount, string $code): array
    {
        Log::info('[CircleAddonSync] creating remote addon', [
            'circle_id' => $circle->id,
            'billing_term' => $term->value,
            'addon_code' => $code,
            'payload_keys' => array_keys($payload),
        ]);

        try {
            $response = $this->client->request('POST', '/addons', $payload);
            return (array) ($response['addon'] ?? []);
        } catch (\Throwable $throwable) {
            Log::warning('[CircleAddonSync] create failed; retrying with fallback payload', [
                'circle_id' => $circle->id,
                'billing_term' => $term->value,
                'addon_code' => $code,
                'error' => $throwable->getMessage(),
            ]);

            $fallbackPayload = $this->payloadBuilder->fallbackBuild($circle, $term, $code, $amount);
            $response = $this->client->request('POST', '/addons', $fallbackPayload);

            return (array) ($response['addon'] ?? []);
        }
    }

    private function updateRemoteAddon(string $remoteId, array $payload): array
    {
        try {
            $response = $this->client->request('PUT', '/addons/' . $remoteId, $payload);
            return (array) ($response['addon'] ?? []);
        } catch (\Throwable $throwable) {
            Log::warning('[CircleAddonSync] update failed; retrying with fallback payload', [
                'zoho_addon_id' => $remoteId,
                'error' => $throwable->getMessage(),
            ]);

            $fallbackPayload = $payload;
            if (array_key_exists('price', $fallbackPayload)) {
                $fallbackPayload['recurring_price'] = $fallbackPayload['price'];
                unset($fallbackPayload['price']);
            }

            $response = $this->client->request('PUT', '/addons/' . $remoteId, $fallbackPayload);

            return (array) ($response['addon'] ?? []);
        }
    }

    private function findRemoteAddonByCode(string $code): ?array
    {
        $response = $this->client->request('GET', '/addons', ['page' => 1, 'per_page' => 200], true);
        $addons = $response['addons'] ?? [];

        foreach ($addons as $addon) {
            if ((string) ($addon['addon_code'] ?? '') === $code) {
                Log::info('[CircleAddonSync] found existing remote by code', [
                    'addon_code' => $code,
                    'zoho_addon_id' => (string) ($addon['addon_id'] ?? ''),
                ]);

                return $addon;
            }
        }

        return null;
    }

    private function saveLocalAddon(
        CircleZohoAddon $model,
        Circle $circle,
        CircleBillingTerm $term,
        float $amount,
        array $payload,
        string $syncHash,
        string $code,
        array $remoteAddon,
        bool $isActive,
    ): void {
        $columns = Schema::getColumnListing($model->getTable());

        $fieldMap = [
            'circle_id' => $circle->id,
            'billing_term' => $term->value,
            'amount' => $amount,
            'price' => $amount,
            'currency' => 'INR',
            'currency_code' => 'INR',
            'zoho_product_id' => (string) env('ZOHO_CIRCLE_ADDON_PRODUCT_ID', ''),
            'zoho_addon_id' => (string) ($remoteAddon['addon_id'] ?? $model->zoho_addon_id ?? ''),
            'zoho_addon_code' => $code,
            'addon_code' => $code,
            'zoho_addon_name' => (string) ($remoteAddon['name'] ?? ($payload['name'] ?? '')),
            'name' => (string) ($remoteAddon['name'] ?? ($payload['name'] ?? '')),
            'description' => (string) ($payload['description'] ?? ''),
            'is_active' => $isActive,
            'sync_hash' => $syncHash,
            'last_synced_at' => now(),
            'raw_payload' => [
                'request' => $payload,
                'response' => $remoteAddon,
            ],
            'metadata' => [
                'request' => $payload,
                'response' => $remoteAddon,
            ],
        ];

        $data = Arr::only($fieldMap, $columns);

        $model->forceFill($data)->save();

        Log::info('[CircleAddonSync] updated local row', [
            'circle_id' => $circle->id,
            'billing_term' => $term->value,
            'addon_code' => $code,
            'zoho_addon_id' => (string) ($data['zoho_addon_id'] ?? ''),
        ]);
    }

    private function markCircleAddonsInactive(Circle $circle): void
    {
        $query = CircleZohoAddon::query()->where('circle_id', $circle->id);

        if (Schema::hasColumn('circle_zoho_addons', 'is_active')) {
            $query->update(['is_active' => false]);
        }
    }

    private function markTermInactive(Circle $circle, CircleBillingTerm $term): void
    {
        $query = CircleZohoAddon::query()
            ->where('circle_id', $circle->id)
            ->where('billing_term', $term->value);

        if (Schema::hasColumn('circle_zoho_addons', 'is_active')) {
            $query->update(['is_active' => false]);
        }
    }
}

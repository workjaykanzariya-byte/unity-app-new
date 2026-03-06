<?php

namespace App\Services\Zoho;

use App\Enums\CircleBillingTerm;
use App\Models\Circle;

class CircleAddonPayloadBuilder
{
    public function buildBase(Circle $circle, CircleBillingTerm $term, string $addonCode, float $amount): array
    {
        return [
            'name' => trim(($circle->name ?: 'Circle') . ' - ' . $term->label()),
            'description' => trim(($circle->description ?: $circle->purpose ?: 'Circle paid subscription') . ' (' . $term->label() . ')'),
            'addon_code' => $addonCode,
            'product_id' => (string) env('ZOHO_CIRCLE_ADDON_PRODUCT_ID', ''),
            'currency_code' => 'INR',
            'price' => round($amount, 2),
        ];
    }

    public function buildPayloadStrategies(Circle $circle, CircleBillingTerm $term, string $addonCode, float $amount, ?array $templateAddon = null): array
    {
        $base = $this->buildBase($circle, $term, $addonCode, $amount);
        $months = $term->months();

        $strategies = [];

        if ($templateAddon) {
            $strategies['template_scheme'] = $this->buildFromTemplate($base, $templateAddon, $months, $amount);
        }

        $strategies['recurring_price_interval'] = array_merge($base, [
            'type' => 'recurring',
            'recurring_price' => round($amount, 2),
            'interval' => $months,
            'interval_unit' => 'months',
        ]);

        $strategies['price_interval'] = array_merge($base, [
            'type' => 'recurring',
            'price' => round($amount, 2),
            'interval' => $months,
            'interval_unit' => 'months',
        ]);

        $strategies['per_unit_brackets'] = array_merge($base, [
            'type' => 'recurring',
            'pricing_scheme' => 'per_unit',
            'interval' => $months,
            'interval_unit' => 'months',
            'price_brackets' => [[
                'start_quantity' => 1,
                'end_quantity' => 0,
                'price' => round($amount, 2),
            ]],
        ]);

        return $strategies;
    }

    public function syncHash(Circle $circle, CircleBillingTerm $term, float $amount, string $name, string $description, bool $active): string
    {
        return hash('sha256', implode('|', [
            (string) env('ZOHO_CIRCLE_ADDON_PRODUCT_ID', ''),
            (string) $circle->id,
            $term->value,
            (string) round($amount, 2),
            $name,
            $description,
            $active ? '1' : '0',
        ]));
    }

    private function buildFromTemplate(array $base, array $templateAddon, int $months, float $amount): array
    {
        $payload = $base;

        foreach (['type', 'pricing_scheme', 'unit', 'product_type'] as $key) {
            if (array_key_exists($key, $templateAddon) && $templateAddon[$key] !== null && $templateAddon[$key] !== '') {
                $payload[$key] = $templateAddon[$key];
            }
        }

        if (array_key_exists('interval_unit', $templateAddon)) {
            $payload['interval_unit'] = 'months';
        }

        if (array_key_exists('interval', $templateAddon)) {
            $payload['interval'] = $months;
        }

        $brackets = $templateAddon['price_brackets'] ?? null;

        if (is_array($brackets) && $brackets !== []) {
            $first = is_array($brackets[0] ?? null) ? $brackets[0] : [];
            $priceKey = array_key_exists('recurring_price', $first) ? 'recurring_price' : 'price';

            $bracket = [
                'start_quantity' => (int) ($first['start_quantity'] ?? 1),
                'end_quantity' => (int) ($first['end_quantity'] ?? 0),
                $priceKey => round($amount, 2),
            ];

            $payload['price_brackets'] = [$bracket];
            unset($payload['price'], $payload['recurring_price']);

            return $payload;
        }

        if (array_key_exists('recurring_price', $templateAddon)) {
            unset($payload['price']);
            $payload['recurring_price'] = round($amount, 2);
        }

        return $payload;
    }
}

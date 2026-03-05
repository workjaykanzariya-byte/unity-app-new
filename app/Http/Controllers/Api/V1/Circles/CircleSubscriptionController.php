<?php

namespace App\Http\Controllers\Api\V1\Circles;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Circle;
use App\Models\CircleMember;
use App\Models\CircleMemberSubscription;
use App\Services\Zoho\ZohoCircleAddonService;
use App\Support\Zoho\ZohoBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CircleSubscriptionController extends BaseApiController
{
    public function __construct(private readonly ZohoBillingService $zohoBillingService)
    {
    }

    public function options(Circle $circle)
    {
        $options = $circle->subscriptionPrices()
            ->where('price', '>', 0)
            ->whereNotNull('zoho_addon_id')
            ->orderBy('duration_months')
            ->get()
            ->map(fn ($row) => [
                'duration_months' => $row->duration_months,
                'label' => ZohoCircleAddonService::durationLabel((int) $row->duration_months),
                'price' => number_format((float) $row->price, 2, '.', ''),
                'zoho_addon_id' => $row->zoho_addon_id,
            ])
            ->values();

        return $this->success([
            'circle_id' => $circle->id,
            'currency' => 'INR',
            'options' => $options,
        ]);
    }

    public function joinWithSubscription(Request $request, Circle $circle)
    {
        $validated = $request->validate([
            'duration_months' => ['required', 'integer', 'in:1,3,6,12'],
            'currency' => ['nullable', 'string', 'max:10'],
        ]);

        $user = $request->user();
        $currency = strtoupper((string) ($validated['currency'] ?? 'INR'));

        if ($circle->founder_user_id === $user->id) {
            return $this->error('You are the founder of this circle', 422);
        }

        $existingMembership = CircleMember::query()
            ->where('circle_id', $circle->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['approved', 'pending'])
            ->first();

        if ($existingMembership) {
            return $this->error('You have already requested to join or are already a member', 422);
        }

        $price = $circle->subscriptionPrices()
            ->where('duration_months', $validated['duration_months'])
            ->where('currency', $currency)
            ->first();

        if (! $price || ! $price->zoho_addon_code) {
            return $this->error('Subscription option not available for selected duration', 422);
        }

        $result = DB::transaction(function () use ($user, $circle, $price, $validated, $currency) {
            $requestModel = CircleMemberSubscription::query()->create([
                'circle_id' => $circle->id,
                'user_id' => $user->id,
                'duration_months' => $validated['duration_months'],
                'price' => $price->price,
                'currency' => $currency,
                'status' => 'pending',
            ]);

            $checkout = $this->zohoBillingService->createHostedPageForCircleSubscription($user, (string) $price->zoho_addon_code);

            $requestModel->forceFill([
                'zoho_hostedpage_id' => $checkout['hostedpage_id'],
                'payload' => $checkout['response'] ?? null,
            ])->save();

            $circlePaymentId = $this->recordCircleJoinPayment(
                userId: (string) $user->id,
                circleId: (string) $circle->id,
                circleFeeId: (string) $price->id,
                hostedPageId: (string) $checkout['hostedpage_id'],
                hostedPageUrl: (string) ($checkout['checkout_url'] ?? ''),
            );

            Log::info('Circle join payment initiated', [
                'circle_id' => $circle->id,
                'user_id' => $user->id,
                'hostedpage_id' => $checkout['hostedpage_id'],
                'circle_join_payment_id' => $circlePaymentId,
            ]);

            return [$requestModel, $checkout, $circlePaymentId];
        });

        [$requestModel, $checkout, $circlePaymentId] = $result;

        return $this->success([
            'subscription_request_id' => $requestModel->id,
            'payment_id' => $circlePaymentId,
            'hostedpage_id' => $checkout['hostedpage_id'],
            'checkout_url' => $checkout['checkout_url'],
        ]);
    }

    private function recordCircleJoinPayment(string $userId, string $circleId, string $circleFeeId, string $hostedPageId, string $hostedPageUrl): ?string
    {
        if (! Schema::hasTable('circle_join_payments')) {
            Log::warning('circle_join_payments table missing; skipping payment tracking row.', [
                'user_id' => $userId,
                'circle_id' => $circleId,
                'hostedpage_id' => $hostedPageId,
            ]);

            return null;
        }

        $payload = [
            'id' => (string) Str::uuid(),
            'user_id' => $userId,
            'circle_id' => $circleId,
            'provider' => 'zoho',
            'status' => 'initiated',
            'zoho_hostedpage_id' => $hostedPageId,
            'zoho_hostedpage_url' => $hostedPageUrl,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('circle_join_payments', 'circle_fee_id')) {
            $payload['circle_fee_id'] = $circleFeeId;
        }

        DB::table('circle_join_payments')->insert($payload);

        return $payload['id'];
    }

}
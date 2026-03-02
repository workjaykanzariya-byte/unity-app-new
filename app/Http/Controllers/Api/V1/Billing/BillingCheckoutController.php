<?php

namespace App\Http\Controllers\Api\V1\Billing;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserMembership;
use App\Support\Membership\MembershipUpdater;
use App\Support\Zoho\ZohoBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class BillingCheckoutController extends Controller
{
    public function __construct(
        private readonly ZohoBillingService $zohoBillingService,
        private readonly MembershipUpdater $membershipUpdater,
    ) {
    }

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'plan_code' => ['required', 'string', 'max:120'],
        ]);

        /** @var User $user */
        $user = $request->user();

        try {
            $result = $this->zohoBillingService->createHostedPageForSubscription($user, $validated['plan_code']);

            $this->recordPendingZohoPayment($user, $validated['plan_code'], (string) $result['hostedpage_id']);

            return response()->json([
                'success' => true,
                'message' => 'Hosted checkout URL created successfully.',
                'data' => [
                    'hostedpage_id' => $result['hostedpage_id'],
                    'checkout_url' => $result['checkout_url'],
                ],
            ]);
        } catch (ValidationException $validationException) {
            return response()->json([
                'success' => false,
                'message' => collect($validationException->errors())->flatten()->first() ?? 'Validation failed',
                'data' => [
                    'errors' => $validationException->errors(),
                ],
            ], 422);
        } catch (Throwable $throwable) {
            Log::error('Zoho checkout creation failed', [
                'user_id' => $user->id,
                'message' => 'Failed to generate checkout URL.',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate checkout URL.',
                'data' => [],
            ], 500);
        }
    }

    public function status(string $hostedpage_id)
    {
        try {
            $payment = Payment::query()
                ->where('provider', 'zoho')
                ->where('zoho_hostedpage_id', $hostedpage_id)
                ->latest('created_at')
                ->first();

            if (! $payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment record not found for hosted page.',
                    'data' => [
                        'hostedpage_id' => $hostedpage_id,
                    ],
                ], 404);
            }

            $user = User::query()->where('id', $payment->user_id)->first();

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found for payment.',
                    'data' => [
                        'hostedpage_id' => $hostedpage_id,
                        'payment_id' => $payment->id,
                    ],
                ], 404);
            }

            $hostedPageResponse = $this->zohoBillingService->getHostedPage($hostedpage_id);
            $hostedpage = $hostedPageResponse['hostedpage'] ?? [];
            $subscription = $hostedpage['subscription'] ?? [];
            $invoice = $hostedpage['invoice'] ?? [];

            if ($hostedpage === [] || $subscription === []) {
                Log::warning('Zoho hosted page response missing hostedpage/subscription', [
                    'hostedpage_id' => $hostedpage_id,
                    'user_id' => $user->id,
                    'response_keys' => array_keys($hostedPageResponse),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Hosted page response is missing subscription details.',
                    'data' => [
                        'hostedpage_id' => $hostedpage_id,
                        'hostedpage_status' => $hostedpage['status'] ?? null,
                    ],
                ], 422);
            }

            $parsed = $this->zohoBillingService->parseHostedPageForMembership($hostedPageResponse);
            $status = strtolower((string) ($parsed['status'] ?? $hostedpage['status'] ?? ''));
            $isSuccess = in_array($status, ['paid', 'success', 'completed', 'active', 'payment_success'], true)
                && ! empty($subscription['subscription_id']);

            if (! $isSuccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment is not completed yet.',
                    'data' => [
                        'hostedpage_status' => $status,
                        'hostedpage_id' => $hostedpage_id,
                    ],
                ]);
            }

            $subscriptionId = $subscription['subscription_id'] ?? $parsed['subscription_id'] ?? null;
            $planCode = $subscription['plan_code'] ?? data_get($subscription, 'plan.plan_code') ?? $payment->zoho_plan_code;
            $startDate = $subscription['current_term_starts_at'] ?? $subscription['created_time'] ?? $parsed['starts_at'] ?? now();
            $endDate = $subscription['current_term_ends_at']
                ?? $subscription['expires_at']
                ?? $parsed['ends_at']
                ?? now()->copy()->addYear()->toDateTimeString();
            $lastInvoiceId = $invoice['invoice_id'] ?? $parsed['invoice_id'] ?? null;

            Log::info('Zoho membership sync parsed values', [
                'hostedpage_id' => $hostedpage_id,
                'user_id' => $user->id,
                'subscription_id' => $subscriptionId,
                'plan_code' => $planCode,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            DB::transaction(function () use ($user, $payment, $subscriptionId, $planCode, $startDate, $endDate, $lastInvoiceId): void {
                $this->membershipUpdater->applyPaidMembership($user, [
                    'zoho_subscription_id' => $subscriptionId,
                    'zoho_plan_code' => $planCode,
                    'zoho_last_invoice_id' => $lastInvoiceId,
                    'membership_starts_at' => $startDate,
                    'membership_ends_at' => $endDate,
                    'last_payment_at' => now(),
                ]);

                $payment->forceFill([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'zoho_plan_code' => $planCode,
                ])->save();

                $this->syncUserMembershipRow($user, $payment, $startDate, $endDate);
            });

            $freshUser = $user->fresh();

            return response()->json([
                'success' => true,
                'message' => 'Membership synced successfully.',
                'data' => [
                    'membership_status' => $freshUser?->membership_status ?? $freshUser?->membership_type ?? $freshUser?->membership ?? 'active',
                    'membership_starts_at' => $freshUser?->membership_starts_at,
                    'membership_ends_at' => $freshUser?->membership_ends_at,
                    'zoho_subscription_id' => $freshUser?->zoho_subscription_id,
                    'zoho_last_invoice_id' => $freshUser?->zoho_last_invoice_id,
                    'zoho_plan_code' => $freshUser?->zoho_plan_code,
                ],
            ]);
        } catch (Throwable $throwable) {
            Log::error('Zoho checkout status sync failed', [
                'hostedpage_id' => $hostedpage_id,
                'message' => $throwable->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $throwable->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    private function recordPendingZohoPayment(User $user, string $planCode, string $hostedpageId): void
    {
        $payment = Payment::query()
            ->where('provider', 'zoho')
            ->where('zoho_hostedpage_id', $hostedpageId)
            ->first();

        if (! $payment) {
            $payment = new Payment();
            $payment->id = (string) Str::uuid();
        }

        $payment->forceFill([
            'user_id' => $user->id,
            'provider' => 'zoho',
            'zoho_plan_code' => $planCode,
            'zoho_hostedpage_id' => $hostedpageId,
            'status' => 'pending',
        ]);

        $payment->save();
    }

    private function syncUserMembershipRow(User $user, Payment $payment, mixed $startsAt, mixed $endsAt): void
    {
        if (! Schema::hasTable('user_memberships')) {
            return;
        }

        $existing = UserMembership::query()
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->first();

        if ($existing) {
            $existing->forceFill([
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => 'active',
                'payment_id' => $payment->id,
            ])->save();

            return;
        }

        try {
            UserMembership::query()->create([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'membership_plan_id' => null,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => 'active',
                'payment_id' => $payment->id,
            ]);
        } catch (Throwable $throwable) {
            Log::warning('Unable to create user_memberships row during Zoho sync', [
                'user_id' => $user->id,
                'payment_id' => $payment->id,
                'error' => $throwable->getMessage(),
            ]);
        }
    }
}

/*
| Postman Smoke Steps
| 1) GET /api/v1/zoho/plans
| 2) POST /api/v1/billing/checkout {"plan_code":"01"}
| 3) Open checkout_url and complete payment
| 4) GET /api/v1/billing/checkout/{hostedpage_id}/status to finalize update
| 5) Webhook can also update automatically.
*/

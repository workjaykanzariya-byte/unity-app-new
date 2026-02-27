<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BillingCheckoutRequest;
use App\Services\Zoho\ZohoBillingService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class BillingCheckoutController extends Controller
{
    public function store(BillingCheckoutRequest $request, ZohoBillingService $zoho): JsonResponse
    {
        $user = $request->user();
        $planCode = (string) $request->validated('plan_code');

        try {
            $customerId = (string) ($user->zoho_customer_id ?? '');

            if ($customerId === '') {
                $existingCustomer = $zoho->findCustomerByEmail((string) $user->email);

                if (is_array($existingCustomer) && ! empty($existingCustomer['customer_id'])) {
                    $customerId = (string) $existingCustomer['customer_id'];
                } else {
                    $displayName = trim((string) ($user->display_name ?? ''));
                    if ($displayName === '') {
                        $displayName = trim(((string) ($user->first_name ?? '')).' '.((string) ($user->last_name ?? '')));
                    }

                    $customer = $zoho->createCustomer([
                        'display_name' => $displayName !== '' ? $displayName : (string) $user->email,
                        'email' => (string) $user->email,
                        'phone' => $user->phone ?: null,
                        'billing_address' => array_filter([
                            'city' => $user->city ?: null,
                            'country' => 'IN',
                        ], static fn ($value): bool => $value !== null && $value !== ''),
                    ]);

                    $customerId = (string) ($customer['customer_id'] ?? '');
                }

                if ($customerId === '') {
                    throw new RuntimeException('Unable to resolve Zoho customer_id.');
                }

                $user->zoho_customer_id = $customerId;
                $user->save();
            }

            $plan = $zoho->getPlanByCode($planCode);

            if (! is_array($plan) || ($plan['status'] ?? null) !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Plan is invalid or inactive.',
                ], 422);
            }

            $hostedPage = $zoho->createSubscriptionHostedPage($customerId, $planCode);
            $checkoutUrl = (string) (
                $hostedPage['url']
                ?? $hostedPage['hostedpage_url']
                ?? $hostedPage['hosted_page_url']
                ?? ''
            );

            if ($checkoutUrl === '') {
                throw new RuntimeException('Zoho hosted checkout URL missing in response.');
            }

            return response()->json([
                'success' => true,
                'checkout_url' => $checkoutUrl,
                'zoho_customer_id' => $customerId,
                'plan_code' => $planCode,
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Zoho error',
                'zoho' => $this->decodeZohoMessage($e->getMessage()),
            ], 502);
        }
    }

    private function decodeZohoMessage(string $message): mixed
    {
        $decoded = json_decode($message, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $message;
    }
}

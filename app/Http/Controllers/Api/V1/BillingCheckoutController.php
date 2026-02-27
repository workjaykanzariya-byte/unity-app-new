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
        $planCode = (string) $request->validated('plan_code');

        try {
            $customerId = $zoho->ensureZohoCustomerForUser($request->user());
            $plan = $zoho->getPlanByCode($planCode);

            if (! is_array($plan) || ($plan['status'] ?? null) !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Plan is invalid or inactive.',
                ], 422);
            }

            $invoice = $zoho->createInvoiceForPlan($customerId, $plan);
            $paymentLink = $zoho->createInvoicePaymentLink((string) $invoice['invoice_id']);
            $paymentUrl = (string) (
                $paymentLink['payment_link_url']
                ?? $paymentLink['url']
                ?? $invoice['payment_url']
                ?? $invoice['invoice_url']
                ?? ''
            );

            if ($paymentUrl === '') {
                throw new RuntimeException('Zoho invoice payment URL missing in response.');
            }

            return response()->json([
                'success' => true,
                'checkout_url' => $paymentUrl,
                'invoice_id' => $invoice['invoice_id'] ?? null,
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

<?php

namespace App\Http\Controllers\Api\V1\Zoho;

use App\Http\Controllers\Controller;
use App\Services\Zoho\ZohoBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ZohoController extends Controller
{
    /**
     * Local test steps:
     * 1) php artisan serve --port=8000
     * 2) ngrok http 8000
     * 3) Update Zoho redirect URLs with ngrok + localhost callback
     * 4) GET /api/v1/zoho/test-token
     * 5) GET /api/v1/zoho/plans
     * 6) POST /api/v1/zoho/customer/ensure
     * 7) POST /api/v1/zoho/checkout {"plan_code":"01"}
     * 8) Open payment_url and complete payment
     */
    public function testToken(ZohoBillingService $zoho): JsonResponse
    {
        try {
            $meta = $zoho->tokenMeta();
            $token = (string) ($meta['access_token'] ?? '');

            return response()->json([
                'success' => true,
                'access_token' => substr($token, 0, 12).'***',
                'api_domain' => $meta['api_domain'] ?? $zoho->billingBaseUrl(),
                'region' => (string) config('services.zoho.dc', 'in'),
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch Zoho token',
                'details' => $this->decodeDetails($e->getMessage()),
            ], 502);
        }
    }

    public function plans(ZohoBillingService $zoho): JsonResponse
    {
        try {
            $response = $zoho->zohoRequest('GET', '/plans');
            $payload = $response->json();

            if (! $response->successful() || ! is_array($payload)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zoho Billing API error',
                    'error_code' => $response->status(),
                    'details' => $payload ?? $response->body(),
                ], 502);
            }

            $plans = collect($payload['plans'] ?? [])->map(static function (array $plan): array {
                $price = $plan['recurring_price'] ?? null;
                if ($price === null && isset($plan['price_brackets'][0]['price'])) {
                    $price = $plan['price_brackets'][0]['price'];
                }

                return [
                    'plan_code' => $plan['plan_code'] ?? null,
                    'name' => $plan['name'] ?? null,
                    'price' => $price,
                    'interval' => ($plan['interval'] ?? null) !== null
                        ? ($plan['interval'].' '.($plan['interval_unit'] ?? ''))
                        : null,
                    'status' => $plan['status'] ?? null,
                ];
            })->values()->all();

            return response()->json([
                'success' => true,
                'plans' => $plans,
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Zoho Billing API error',
                'details' => $this->decodeDetails($e->getMessage()),
            ], 502);
        }
    }

    public function ensureCustomer(Request $request, ZohoBillingService $zoho): JsonResponse
    {
        try {
            $customerId = $zoho->ensureZohoCustomerForUser($request->user());

            return response()->json([
                'success' => true,
                'zoho_customer_id' => $customerId,
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Zoho error',
                'details' => $this->decodeDetails($e->getMessage()),
            ], 502);
        }
    }

    public function checkout(Request $request, ZohoBillingService $zoho): JsonResponse
    {
        $validated = $request->validate([
            'plan_code' => ['required', 'string', 'max:120'],
        ]);

        $planCode = (string) $validated['plan_code'];

        try {
            $customerId = $zoho->ensureZohoCustomerForUser($request->user());
            $plan = $zoho->getPlanByCode($planCode);

            if (! is_array($plan) || (string) ($plan['status'] ?? '') !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected plan is invalid or inactive.',
                    'error_code' => 'PLAN_INACTIVE',
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
                'payment_url' => $paymentUrl,
                'invoice_id' => $invoice['invoice_id'] ?? null,
                'plan_code' => $planCode,
                'zoho_customer_id' => $customerId,
            ]);
        } catch (RuntimeException $e) {
            Log::error('Zoho checkout failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Zoho error',
                'details' => $this->decodeDetails($e->getMessage()),
            ], 502);
        }
    }

    private function decodeDetails(string $message): mixed
    {
        $decoded = json_decode($message, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $message;
    }
}

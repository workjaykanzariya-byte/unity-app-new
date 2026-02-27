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
     * 3) Add ngrok + http://127.0.0.1:8000 callback URLs in Zoho app settings
     * 4) GET /api/v1/zoho/test-token
     * 5) GET /api/v1/zoho/plans
     * 6) POST /api/v1/zoho/checkout {"plan_code":"01"}
     * 7) Open hosted_page_url and verify no portal-access red error
     * 8) Verify Razorpay options appear and proceed
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
            Log::error('Zoho test-token failed', ['error' => $e->getMessage()]);

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
                Log::error('Zoho plans failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Zoho Billing API error',
                    'error_code' => $response->status(),
                    'details' => $payload ?? $response->body(),
                ], 502);
            }

            $plans = collect($payload['plans'] ?? [])->map(static function (array $p): array {
                return [
                    'plan_code' => $p['plan_code'] ?? null,
                    'name' => $p['name'] ?? null,
                    'price' => $p['recurring_price'] ?? ($p['price'] ?? null),
                    'interval' => ($p['interval'] ?? null) !== null
                        ? ($p['interval'].' '.($p['interval_unit'] ?? ''))
                        : null,
                    'status' => $p['status'] ?? null,
                ];
            })->values()->all();

            return response()->json([
                'success' => true,
                'plans' => $plans,
            ]);
        } catch (RuntimeException $e) {
            Log::error('Zoho plans exception', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Zoho Billing API error',
                'details' => $this->decodeDetails($e->getMessage()),
            ], 502);
        }
    }

    public function checkout(Request $request, ZohoBillingService $zoho): JsonResponse
    {
        $validated = $request->validate([
            'plan_code' => ['required', 'string', 'max:120'],
        ]);

        $user = $request->user();
        $planCode = (string) $validated['plan_code'];

        try {
            $customerId = $zoho->ensureZohoCustomerForUser($user);
            $plan = $zoho->getPlanByCode($planCode);

            if (! is_array($plan) || (string) ($plan['status'] ?? '') !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected plan is invalid or inactive.',
                    'error_code' => 'PLAN_INACTIVE',
                ], 422);
            }

            $appUrl = rtrim((string) config('app.url', 'http://127.0.0.1:8000'), '/');
            $hosted = $zoho->createSubscriptionHostedPage($customerId, $planCode, [
                'redirect_url' => $appUrl.'/billing/success',
                'cancel_url' => $appUrl.'/billing/cancel',
            ]);

            return response()->json([
                'success' => true,
                'hosted_page_url' => $hosted['url'] ?? $hosted['hostedpage_url'] ?? null,
                'hostedpage_id' => $hosted['hostedpage_id'] ?? $hosted['hosted_page_id'] ?? null,
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

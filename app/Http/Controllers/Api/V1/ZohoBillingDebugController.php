<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Zoho\ZohoBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ZohoBillingDebugController extends Controller
{
    public function org(ZohoBillingService $zoho): JsonResponse
    {
        $orgId = (string) config('services.zoho.org_id');

        if ($orgId === '') {
            return response()->json([
                'success' => false,
                'message' => 'Missing ZOHO_BILLING_ORG_ID configuration.',
            ], 422);
        }

        try {
            $response = Http::withToken($zoho->getAccessToken(), 'Zoho-oauthtoken')
                ->get($zoho->billingBaseUrl().'/organizations/'.$orgId, [
                    'organization_id' => $orgId,
                ]);

            if (! $response->successful()) {
                return $this->zohoErrorResponse($response);
            }

            $payload = $response->json();

            return response()->json([
                'success' => true,
                'organization' => $payload['organization'] ?? $payload,
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 502);
        }
    }

    public function plans(ZohoBillingService $zoho): JsonResponse
    {
        $orgId = (string) config('services.zoho.org_id');

        if ($orgId === '') {
            return response()->json([
                'success' => false,
                'message' => 'Missing ZOHO_BILLING_ORG_ID configuration.',
            ], 422);
        }

        try {
            $response = Http::withToken($zoho->getAccessToken(), 'Zoho-oauthtoken')
                ->get($zoho->billingBaseUrl().'/plans', [
                    'organization_id' => $orgId,
                ]);

            if (! $response->successful()) {
                return $this->zohoErrorResponse($response);
            }

            $payload = $response->json();
            $plans = collect($payload['plans'] ?? [])
                ->filter(fn (array $plan): bool => ($plan['status'] ?? null) === 'active')
                ->map(function (array $plan): array {
                    $recurringPrice = $plan['recurring_price'] ?? null;
                    $priceBrackets = $plan['price_brackets'] ?? [];
                    $priceFromBracket = is_array($priceBrackets) && isset($priceBrackets[0]) && is_array($priceBrackets[0])
                        ? ($priceBrackets[0]['price'] ?? null)
                        : null;

                    $interval = $plan['interval'] ?? null;
                    $intervalUnit = (string) ($plan['interval_unit'] ?? '');
                    $normalizedUnit = $intervalUnit;

                    if ($interval === 1 && $intervalUnit !== '' && str_ends_with($intervalUnit, 's')) {
                        $normalizedUnit = substr($intervalUnit, 0, -1);
                    }

                    return [
                        'plan_code' => $plan['plan_code'] ?? null,
                        'name' => $plan['name'] ?? null,
                        'price' => $recurringPrice ?? $priceFromBracket,
                        'currency_code' => $plan['currency_code'] ?? null,
                        'interval' => ($interval !== null && $normalizedUnit !== '') ? $interval.' '.$normalizedUnit : null,
                        'status' => $plan['status'] ?? null,
                        'description' => $plan['description'] ?? null,
                    ];
                })
                ->values()
                ->all();

            return response()->json([
                'success' => true,
                'plans' => $plans,
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 502);
        }
    }

    private function zohoErrorResponse(Response $response): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Zoho Billing API error',
            'zoho_status' => $response->status(),
            'zoho_body' => $response->body(),
        ], 502);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Zoho\ZohoBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ZohoDebugController extends Controller
{
    public function token(Request $request, ZohoBillingService $zoho): JsonResponse
    {
        if (! app()->environment('local') && ! $request->user()) {
            abort(404);
        }

        try {
            $tokenData = $zoho->tokenMeta();
            $accessToken = (string) ($tokenData['access_token'] ?? '');

            return response()->json([
                'success' => true,
                'access_token' => $this->maskToken($accessToken),
                'expires_in' => $tokenData['expires_in'] ?? null,
                'api_domain' => $tokenData['api_domain'] ?? null,
                'region' => (string) config('services.zoho.dc', 'in'),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to refresh Zoho access token.',
                'error' => $e->getMessage(),
            ], 502);
        }
    }

    private function maskToken(string $token): string
    {
        if ($token === '') {
            return '';
        }

        return substr($token, 0, 12).'***';
    }
}

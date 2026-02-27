<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ZohoOAuthController extends Controller
{
    /**
     * Deployment note:
     * - Run: php artisan route:clear && php artisan optimize:clear
     * - Open: /api/v1/zoho/auth to get auth_url
     * - Open auth_url in browser, consent, then Zoho redirects to /api/v1/zoho/callback?code=...
     * - Copy refresh_token into .env (ZOHO_REFRESH_TOKEN or your chosen secret store key).
     */
    public function redirect(Request $request): JsonResponse
    {
        $state = Str::random(40);

        $query = http_build_query([
            'client_id' => (string) config('services.zoho.client_id'),
            'scope' => 'ZohoSubscriptions.settings.READ,ZohoSubscriptions.plans.READ,ZohoSubscriptions.customers.CREATE,ZohoSubscriptions.customers.READ,ZohoSubscriptions.subscriptions.CREATE,ZohoSubscriptions.subscriptions.READ,ZohoSubscriptions.invoices.READ',
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'redirect_uri' => (string) config('services.zoho.redirect_uri'),
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);

        return response()->json([
            'success' => true,
            'auth_url' => 'https://accounts.zoho.in/oauth/v2/auth?'.$query,
            'dc' => (string) config('services.zoho.dc', 'in'),
            'billing_api_base' => 'https://www.zohoapis.in/billing/v1',
        ]);
    }

    public function callback(Request $request): JsonResponse
    {
        $code = (string) $request->query('code', '');
        $state = (string) $request->query('state', '');

        if ($code === '') {
            return response()->json([
                'success' => false,
                'message' => 'Missing authorization code.',
            ], 422);
        }

        $response = Http::asForm()->post('https://accounts.zoho.in/oauth/v2/token', [
            'grant_type' => 'authorization_code',
            'client_id' => (string) config('services.zoho.client_id'),
            'client_secret' => (string) config('services.zoho.client_secret'),
            'redirect_uri' => (string) config('services.zoho.redirect_uri'),
            'code' => $code,
        ]);

        $payload = $response->json();

        if (! $response->successful() || ! is_array($payload)) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to exchange authorization code for Zoho tokens.',
                'zoho_response' => $payload,
            ], $response->status() > 0 ? $response->status() : 502);
        }

        $accessToken = $payload['access_token'] ?? null;
        $refreshToken = $payload['refresh_token'] ?? null;
        $expiresIn = $payload['expires_in'] ?? ($payload['expires_in_sec'] ?? null);
        $apiDomain = $payload['api_domain'] ?? null;

        if (is_string($accessToken) && $accessToken !== '') {
            Cache::put('zoho.oauth.access_token', $accessToken, now()->addMinutes(55));
        }

        if (is_string($refreshToken) && $refreshToken !== '') {
            Cache::put('zoho.oauth.refresh_token', $refreshToken, now()->addDays(30));
        }

        return response()->json([
            'success' => true,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $expiresIn,
            'api_domain' => $apiDomain,
            'state' => $state !== '' ? $state : null,
        ]);
    }
}

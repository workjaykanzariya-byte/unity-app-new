<?php

namespace App\Support\Zoho;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ZohoBillingTokenService
{
    public function getAccessToken(): string
    {
        $cacheKey = (string) config('zoho_billing.token_cache_key', 'zoho_billing_access_token');

        return Cache::remember($cacheKey, now()->addMinutes(50), function (): string {
            $token = $this->refreshAccessToken();

            return $token['access_token'];
        });
    }

    /**
     * @return array{access_token:string,expires_in:int,token_type:?string}
     */
    public function refreshAccessToken(): array
    {
        $clientId = (string) config('zoho_billing.client_id');
        $clientSecret = (string) config('zoho_billing.client_secret');
        $refreshToken = (string) config('zoho_billing.refresh_token');
        $redirectUri = config('zoho_billing.redirect_uri');
        $tokenUrl = (string) config('zoho_billing.oauth_token_url', 'https://accounts.zoho.in/oauth/v2/token');

        if ($clientId === '' || $clientSecret === '' || $refreshToken === '') {
            throw new RuntimeException('Zoho Billing OAuth configuration is incomplete.');
        }

        $payload = [
            'grant_type' => 'refresh_token',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
        ];

        if (! empty($redirectUri)) {
            $payload['redirect_uri'] = $redirectUri;
        }

        $response = Http::asForm()
            ->timeout((int) config('zoho_billing.timeout_seconds', 20))
            ->retry(
                (int) config('zoho_billing.retry_times', 2),
                (int) config('zoho_billing.retry_sleep_milliseconds', 200)
            )
            ->post($tokenUrl, $payload);

        if (! $response->successful()) {
            Log::error('Zoho token refresh failed', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new RuntimeException('Unable to refresh Zoho Billing access token.');
        }

        $json = $response->json();
        $accessToken = (string) ($json['access_token'] ?? '');
        $expiresIn = (int) ($json['expires_in'] ?? 3600);

        if ($accessToken === '') {
            throw new RuntimeException('Zoho token refresh response missing access_token.');
        }

        $ttlSeconds = max(60, $expiresIn - 120);
        Cache::put(
            (string) config('zoho_billing.token_cache_key', 'zoho_billing_access_token'),
            $accessToken,
            now()->addSeconds($ttlSeconds)
        );

        return [
            'access_token' => $accessToken,
            'expires_in' => $expiresIn,
            'token_type' => $json['token_type'] ?? null,
        ];
    }
}

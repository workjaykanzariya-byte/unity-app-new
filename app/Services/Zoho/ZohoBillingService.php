<?php

namespace App\Services\Zoho;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ZohoBillingService
{
    public function getAccessToken(): string
    {
        $cacheKey = $this->tokenCacheKey();
        $cached = Cache::get($cacheKey);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $tokenData = $this->refreshAccessToken();

        Cache::put($cacheKey, $tokenData['access_token'], now()->addMinutes(55));

        return $tokenData['access_token'];
    }

    public function billingBaseUrl(): string
    {
        return 'https://www.zohoapis.'.$this->regionTld().'/billing/v1';
    }

    public function tokenMeta(): array
    {
        $tokenData = $this->refreshAccessToken();

        Cache::put($this->tokenCacheKey(), $tokenData['access_token'], now()->addMinutes(55));

        return $tokenData;
    }

    public function findCustomerByEmail(string $email): ?array
    {
        $response = Http::withToken($this->getAccessToken(), 'Zoho-oauthtoken')
            ->get($this->billingBaseUrl().'/customers', [
                'organization_id' => (string) config('services.zoho.org_id'),
                'email_contains' => $email,
            ]);

        $payload = $response->json();

        if (! $response->successful() || ! is_array($payload)) {
            throw new RuntimeException(json_encode($payload));
        }

        $customers = $payload['customers'] ?? [];
        if (! is_array($customers) || $customers === []) {
            return null;
        }

        foreach ($customers as $customer) {
            if (is_array($customer) && strtolower((string) ($customer['email'] ?? '')) === strtolower($email)) {
                return $customer;
            }
        }

        return is_array($customers[0] ?? null) ? $customers[0] : null;
    }

    public function createCustomer(array $payload): array
    {
        $response = Http::withToken($this->getAccessToken(), 'Zoho-oauthtoken')
            ->post($this->billingBaseUrl().'/customers?organization_id='.(string) config('services.zoho.org_id'), $payload);

        $body = $response->json();

        if (! $response->successful() || ! is_array($body)) {
            throw new RuntimeException(json_encode($body));
        }

        $customer = $body['customer'] ?? null;
        if (! is_array($customer) || empty($customer['customer_id'])) {
            throw new RuntimeException(json_encode($body));
        }

        return $customer;
    }

    public function getPlanByCode(string $planCode): ?array
    {
        $response = Http::withToken($this->getAccessToken(), 'Zoho-oauthtoken')
            ->get($this->billingBaseUrl().'/plans', [
                'organization_id' => (string) config('services.zoho.org_id'),
            ]);

        $payload = $response->json();

        if (! $response->successful() || ! is_array($payload)) {
            throw new RuntimeException(json_encode($payload));
        }

        $plans = $payload['plans'] ?? [];
        if (! is_array($plans)) {
            return null;
        }

        foreach ($plans as $plan) {
            if (is_array($plan) && (string) ($plan['plan_code'] ?? '') === $planCode) {
                return $plan;
            }
        }

        return null;
    }

    public function createSubscriptionHostedPage(string $customerId, string $planCode): array
    {
        $response = Http::withToken($this->getAccessToken(), 'Zoho-oauthtoken')
            ->post($this->billingBaseUrl().'/hostedpages/newsubscription?organization_id='.(string) config('services.zoho.org_id'), [
                'customer' => [
                    'customer_id' => $customerId,
                ],
                'plan' => [
                    'plan_code' => $planCode,
                ],
            ]);

        $payload = $response->json();

        if (! $response->successful() || ! is_array($payload)) {
            throw new RuntimeException(json_encode($payload));
        }

        $hostedPage = $payload['hostedpage'] ?? null;
        if (! is_array($hostedPage)) {
            throw new RuntimeException(json_encode($payload));
        }

        return $hostedPage;
    }

    private function refreshAccessToken(): array
    {
        $response = Http::asForm()->post(
            'https://accounts.zoho.'.$this->regionTld().'/oauth/v2/token',
            [
                'refresh_token' => (string) config('services.zoho.refresh_token'),
                'client_id' => (string) config('services.zoho.client_id'),
                'client_secret' => (string) config('services.zoho.client_secret'),
                'grant_type' => 'refresh_token',
            ]
        );

        $payload = $response->json();

        if (! $response->successful() || ! is_array($payload)) {
            throw new RuntimeException('Zoho token refresh failed: '.json_encode($payload));
        }

        $accessToken = $payload['access_token'] ?? null;

        if (! is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('Zoho token refresh failed: access_token missing.');
        }

        return [
            'access_token' => $accessToken,
            'expires_in' => $payload['expires_in'] ?? ($payload['expires_in_sec'] ?? 3600),
            'api_domain' => $payload['api_domain'] ?? null,
        ];
    }

    private function regionTld(): string
    {
        return match ((string) config('services.zoho.dc', 'in')) {
            'us' => 'com',
            'eu' => 'eu',
            default => 'in',
        };
    }

    private function tokenCacheKey(): string
    {
        $orgId = (string) config('services.zoho.org_id', 'default');

        return 'zoho.billing.access_token.'.$orgId;
    }
}

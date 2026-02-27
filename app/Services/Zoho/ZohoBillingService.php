<?php

namespace App\Services\Zoho;

use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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

    public function zohoRequest(string $method, string $path, array $query = [], array $json = []): Response
    {
        $query = array_merge([
            'organization_id' => (string) config('services.zoho.org_id'),
        ], $query);

        $request = Http::withToken($this->getAccessToken(), 'Zoho-oauthtoken');
        $url = rtrim($this->billingBaseUrl(), '/').'/'.ltrim($path, '/');

        if (strtoupper($method) === 'GET') {
            return $request->get($url, $query);
        }

        return $request->send(strtoupper($method), $url.'?'.http_build_query($query), [
            'json' => $json,
        ]);
    }

    public function findCustomerByEmail(string $email): ?array
    {
        $response = $this->zohoRequest('GET', '/customers', [
            'email_contains' => $email,
        ]);

        $payload = $response->json();

        if (! $response->successful() || ! is_array($payload)) {
            $this->logZohoError('findCustomerByEmail failed', $response);
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

    public function createCustomerWithContactPerson(User $user): array
    {
        $firstName = (string) ($user->first_name ?? '');
        $lastName = (string) ($user->last_name ?? '');
        $email = (string) $user->email;

        if ($email === '') {
            throw new RuntimeException('User email is required to create Zoho customer.');
        }

        $displayName = trim((string) ($user->display_name ?? ''));
        if ($displayName === '') {
            $displayName = trim($firstName.' '.$lastName);
        }
        if ($displayName === '') {
            $displayName = $email;
        }

        $payload = [
            'display_name' => $displayName,
            'customer_name' => $displayName,
            'email' => $email,
            'phone' => $user->phone ?: null,
            'billing_address' => array_filter([
                'city' => $user->city ?: null,
                'country' => 'IN',
            ], static fn ($v): bool => $v !== null && $v !== ''),
            'contact_persons' => [[
                'first_name' => $firstName !== '' ? $firstName : $displayName,
                'last_name' => $lastName,
                'email' => $email,
                'is_primary_contact' => true,
                'enable_portal' => true,
            ]],
        ];

        return $this->createCustomer($payload);
    }

    public function createCustomer(array $payload): array
    {
        $response = $this->zohoRequest('POST', '/customers', [], $payload);
        $body = $response->json();

        if (! $response->successful() || ! is_array($body)) {
            $this->logZohoError('createCustomer failed', $response);
            throw new RuntimeException(json_encode($body));
        }

        $customer = $body['customer'] ?? null;

        if (! is_array($customer) || empty($customer['customer_id'])) {
            throw new RuntimeException(json_encode($body));
        }

        return $customer;
    }

    public function getCustomer(string $customerId): array
    {
        $response = $this->zohoRequest('GET', '/customers/'.$customerId);
        $payload = $response->json();

        if (! $response->successful() || ! is_array($payload)) {
            $this->logZohoError('getCustomer failed', $response);
            throw new RuntimeException(json_encode($payload));
        }

        $customer = $payload['customer'] ?? null;

        if (! is_array($customer) || empty($customer['customer_id'])) {
            throw new RuntimeException(json_encode($payload));
        }

        return $customer;
    }

    public function createContactPerson(string $customerId, User $user): array
    {
        $email = (string) $user->email;
        if ($email === '') {
            throw new RuntimeException('User email is required to create contact person.');
        }

        $payload = [
            'first_name' => (string) ($user->first_name ?? ''),
            'last_name' => (string) ($user->last_name ?? ''),
            'email' => $email,
            'is_primary_contact' => true,
            'enable_portal' => true,
        ];

        $response = $this->zohoRequest('POST', '/customers/'.$customerId.'/contactpersons', [], $payload);
        $body = $response->json();

        if (! $response->successful() || ! is_array($body)) {
            $this->logZohoError('createContactPerson failed', $response);
            throw new RuntimeException(json_encode($body));
        }

        $contact = $body['contact_person'] ?? null;

        if (! is_array($contact) || empty($contact['contact_person_id'])) {
            throw new RuntimeException(json_encode($body));
        }

        return $contact;
    }

    public function updateContactPersonPortalAndPrimary(string $customerId, string $contactPersonId): array
    {
        $response = $this->zohoRequest('PUT', '/customers/'.$customerId.'/contactpersons/'.$contactPersonId, [], [
            'is_primary_contact' => true,
            'enable_portal' => true,
        ]);

        $body = $response->json();

        if (! $response->successful() || ! is_array($body)) {
            $this->logZohoError('updateContactPersonPortalAndPrimary failed', $response);
            throw new RuntimeException(json_encode($body));
        }

        return $body['contact_person'] ?? $body;
    }

    public function ensurePrimaryPortalContactPerson(string $customerId, User $user): array
    {
        $email = strtolower((string) $user->email);
        $customer = $this->getCustomer($customerId);
        $contactPersons = $customer['contact_persons'] ?? [];

        $existing = null;

        if (is_array($contactPersons)) {
            foreach ($contactPersons as $contact) {
                if (is_array($contact) && strtolower((string) ($contact['email'] ?? '')) === $email) {
                    $existing = $contact;
                    break;
                }
            }
        }

        if (! is_array($existing)) {
            $existing = $this->createContactPerson($customerId, $user);
        }

        $contactId = (string) ($existing['contact_person_id'] ?? '');

        if ($contactId === '') {
            throw new RuntimeException('Unable to resolve contact_person_id for Zoho customer.');
        }

        return $this->updateContactPersonPortalAndPrimary($customerId, $contactId);
    }

    public function ensureZohoCustomerForUser(User $user): string
    {
        $customerId = (string) ($user->zoho_customer_id ?? '');

        if ($customerId === '') {
            $found = $this->findCustomerByEmail((string) $user->email);

            if (is_array($found) && ! empty($found['customer_id'])) {
                $customerId = (string) $found['customer_id'];
            } else {
                $created = $this->createCustomerWithContactPerson($user);
                $customerId = (string) ($created['customer_id'] ?? '');
            }
        }

        if ($customerId === '') {
            throw new RuntimeException('Unable to resolve Zoho customer_id.');
        }

        if (Schema::hasColumn('users', 'zoho_customer_id')) {
            $user->zoho_customer_id = $customerId;
            $user->save();
        }

        $this->ensurePrimaryPortalContactPerson($customerId, $user);

        return $customerId;
    }

    public function getPlanByCode(string $planCode): ?array
    {
        $response = $this->zohoRequest('GET', '/plans');
        $payload = $response->json();

        if (! $response->successful() || ! is_array($payload)) {
            $this->logZohoError('getPlanByCode failed', $response);
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

    public function createSubscriptionHostedPage(string $customerId, string $planCode, array $extra = []): array
    {
        $payload = array_merge([
            'customer' => ['customer_id' => $customerId],
            'plan' => ['plan_code' => $planCode],
        ], $extra);

        $response = $this->zohoRequest('POST', '/hostedpages/newsubscription', [], $payload);
        $body = $response->json();

        if (! $response->successful() || ! is_array($body)) {
            $this->logZohoError('createSubscriptionHostedPage failed', $response);
            throw new RuntimeException(json_encode($body));
        }

        $hostedPage = $body['hostedpage'] ?? $body['hosted_page'] ?? null;

        if (! is_array($hostedPage)) {
            throw new RuntimeException(json_encode($body));
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
            'api_domain' => 'https://www.zohoapis.'.$this->regionTld(),
        ];
    }

    private function logZohoError(string $context, Response $response): void
    {
        Log::error($context, [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
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
        return 'zoho.billing.access_token.'.(string) config('services.zoho.org_id', 'default');
    }
}

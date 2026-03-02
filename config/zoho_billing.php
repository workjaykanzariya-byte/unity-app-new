<?php

return [
    'region' => env('ZOHO_REGION', 'in'),
    'api_domain' => env('ZOHO_API_DOMAIN', 'https://www.zohoapis.in'),
    'base_url' => env('ZOHO_BILLING_BASE_URL', 'https://www.zohoapis.in/billing/v1'),
    'organization_id' => env('ZOHO_ORG_ID'),
    'client_id' => env('ZOHO_CLIENT_ID'),
    'client_secret' => env('ZOHO_CLIENT_SECRET'),
    'refresh_token' => env('ZOHO_REFRESH_TOKEN'),
    'redirect_uri' => env('ZOHO_REDIRECT_URI'),
    'webhook_secret' => env('ZOHO_WEBHOOK_SECRET'),
    'portal_demo_email_prefix' => env('ZOHO_PORTAL_DEMO_EMAIL_PREFIX', 'demo'),
    'oauth_token_url' => env('ZOHO_OAUTH_TOKEN_URL', 'https://accounts.zoho.in/oauth/v2/token'),
    'timeout_seconds' => (int) env('ZOHO_HTTP_TIMEOUT_SECONDS', 20),
    'retry_times' => (int) env('ZOHO_HTTP_RETRY_TIMES', 2),
    'retry_sleep_milliseconds' => (int) env('ZOHO_HTTP_RETRY_SLEEP_MS', 200),
    'token_cache_key' => env('ZOHO_TOKEN_CACHE_KEY', 'zoho_billing_access_token'),
];

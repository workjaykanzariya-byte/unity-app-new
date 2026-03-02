<?php

namespace App\Http\Controllers\Api\V1\Zoho;

use App\Http\Controllers\Controller;
use App\Support\Zoho\ZohoBillingService;
use App\Support\Zoho\ZohoBillingTokenService;
use Throwable;

class ZohoDebugController extends Controller
{
    public function __construct(
        private readonly ZohoBillingTokenService $tokenService,
        private readonly ZohoBillingService $billingService
    ) {
    }

    public function testToken()
    {
        try {
            $meta = $this->tokenService->refreshAccessToken();

            return response()->json([
                'success' => true,
                'message' => 'Zoho token refreshed successfully.',
                'data' => [
                    'expires_in' => $meta['expires_in'],
                    'token_type' => $meta['token_type'],
                    'organization_id' => config('zoho_billing.organization_id'),
                ],
            ]);
        } catch (Throwable $throwable) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh Zoho token.',
                'data' => ['error' => $throwable->getMessage()],
            ], 500);
        }
    }

    public function organization()
    {
        try {
            $organization = $this->billingService->getOrganization();

            return response()->json([
                'success' => true,
                'message' => 'Zoho organization fetched successfully.',
                'data' => $organization,
            ]);
        } catch (Throwable $throwable) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch Zoho organization.',
                'data' => ['error' => $throwable->getMessage()],
            ], 500);
        }
    }
}

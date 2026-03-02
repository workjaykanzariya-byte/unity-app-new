<?php

namespace App\Http\Controllers\Api\V1\Zoho;

use App\Http\Controllers\Controller;
use App\Support\Zoho\ZohoBillingService;
use Throwable;

class ZohoPlansController extends Controller
{
    public function __construct(private readonly ZohoBillingService $billingService)
    {
    }

    public function index()
    {
        try {
            $plans = $this->billingService->listActivePlans();

            return response()->json([
                'success' => true,
                'message' => 'Active plans fetched successfully.',
                'data' => ['plans' => $plans],
            ]);
        } catch (Throwable $throwable) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch active plans.',
                'data' => ['error' => $throwable->getMessage()],
            ], 500);
        }
    }
}

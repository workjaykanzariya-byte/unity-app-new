<?php

namespace App\Http\Controllers\Api\V1\Zoho;

use App\Http\Controllers\Controller;
use App\Support\Zoho\ZohoBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ZohoWebhookController extends Controller
{
    public function __construct(private readonly ZohoBillingService $billingService)
    {
    }

    public function handle(Request $request)
    {
        $secret = (string) $request->query('secret', '');
        $expected = (string) config('zoho_billing.webhook_secret');

        if ($secret === '' || ! hash_equals($expected, $secret)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized webhook request.',
                'data' => [],
            ], 401);
        }

        $event = $request->all();

        try {
            $updated = $this->billingService->applyWebhookEvent($event);

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully.',
                'data' => [
                    'event_type' => $event['event_type'] ?? null,
                    'membership_updated' => $updated,
                ],
            ]);
        } catch (Throwable $throwable) {
            Log::error('Zoho webhook processing failed', [
                'error' => $throwable->getMessage(),
                'event' => $event,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed.',
                'data' => ['error' => $throwable->getMessage()],
            ], 500);
        }
    }
}

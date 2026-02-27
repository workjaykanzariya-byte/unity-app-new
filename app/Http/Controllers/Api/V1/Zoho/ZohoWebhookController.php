<?php

namespace App\Http\Controllers\Api\V1\Zoho;

use App\Http\Controllers\Controller;
use App\Services\Zoho\ZohoBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ZohoWebhookController extends Controller
{
    public function handle(Request $request, ZohoBillingService $zoho): JsonResponse
    {
        $payloadRaw = (string) $request->getContent();
        $payload = $request->json()->all();

        Log::info('Zoho billing webhook payload', ['payload' => $payload]);

        if (! $this->verifySignature($request, $payloadRaw)) {
            return response()->json(['success' => false, 'message' => 'Invalid webhook signature'], 401);
        }

        $event = (string) ($payload['event_type'] ?? $payload['event'] ?? '');

        if (! in_array($event, ['invoice.paid', 'payment.success', 'payment.paid'], true)) {
            return response()->json(['success' => true, 'message' => 'Event ignored']);
        }

        $invoice = $payload['invoice'] ?? [];
        $customerId = (string) ($invoice['customer_id'] ?? $payload['customer']['customer_id'] ?? '');

        if ($customerId === '') {
            return response()->json(['success' => false, 'message' => 'Missing customer_id in webhook payload'], 422);
        }

        $user = $zoho->findUserByZohoCustomerId($customerId);

        if (! $user) {
            return response()->json(['success' => true, 'message' => 'No matching user for customer']);
        }

        $planCode = (string) ($invoice['reference_number'] ?? $invoice['plan']['plan_code'] ?? '');

        if (Schema::hasColumn('users', 'zoho_last_invoice_id')) {
            $user->zoho_last_invoice_id = $invoice['invoice_id'] ?? null;
        }

        if (Schema::hasColumn('users', 'last_payment_at')) {
            $user->last_payment_at = now();
        }

        if (Schema::hasColumn('users', 'membership_status')) {
            $user->membership_status = 'active';
        }

        if (Schema::hasColumn('users', 'membership_starts_at')) {
            $user->membership_starts_at = now();
        }

        if (Schema::hasColumn('users', 'zoho_plan_code') && $planCode !== '') {
            $user->zoho_plan_code = $planCode;
        }

        $subscriptionId = (string) ($payload['subscription']['subscription_id'] ?? $invoice['subscription_id'] ?? '');
        if (Schema::hasColumn('users', 'zoho_subscription_id') && $subscriptionId !== '') {
            $user->zoho_subscription_id = $subscriptionId;
        }

        if (Schema::hasColumn('users', 'membership_ends_at')) {
            try {
                $user->membership_ends_at = $planCode !== ''
                    ? $zoho->computeMembershipEndAt($planCode)
                    : now()->addYear();
            } catch (RuntimeException) {
                $user->membership_ends_at = now()->addYear();
            }
        }

        $user->save();

        return response()->json(['success' => true]);
    }

    private function verifySignature(Request $request, string $payload): bool
    {
        $secret = (string) config('services.zoho.webhook_secret');

        if ($secret === '') {
            return true;
        }

        $signature = (string) $request->header('X-Zoho-Webhook-Signature', '');

        if ($signature === '') {
            return false;
        }

        $hex = hash_hmac('sha256', $payload, $secret);
        $base64 = base64_encode(hex2bin($hex));

        return hash_equals($hex, $signature) || hash_equals($base64, $signature);
    }
}

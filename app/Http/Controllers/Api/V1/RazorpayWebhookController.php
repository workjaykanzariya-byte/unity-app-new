<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\User;
use App\Services\MembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RazorpayWebhookController extends Controller
{
    public function __construct(private readonly MembershipService $membershipService)
    {
    }

    public function handle(Request $request): JsonResponse
    {
        $signature = (string) $request->header('X-Razorpay-Signature');
        $payload = (string) $request->getContent();
        $secret = (string) config('razorpay.webhook_secret');

        if ($signature === '' || $secret === '') {
            Log::warning('Razorpay webhook signature or secret missing');

            return response()->json(['ok' => true], 403);
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        if (! hash_equals($expectedSignature, $signature)) {
            Log::warning('Razorpay webhook signature mismatch');

            return response()->json(['ok' => true], 403);
        }

        $data = json_decode($payload, true);
        if (! is_array($data)) {
            Log::warning('Razorpay webhook payload invalid');

            return response()->json(['ok' => true]);
        }

        $event = $data['event'] ?? '';

        if ($event === 'payment.captured') {
            $this->handlePaymentCaptured($data);
        }

        if ($event === 'payment.failed') {
            $this->handlePaymentFailed($data);
        }

        return response()->json(['ok' => true]);
    }

    private function handlePaymentCaptured(array $payload): void
    {
        $paymentEntity = $payload['payload']['payment']['entity'] ?? [];
        $orderId = $paymentEntity['order_id'] ?? null;

        if (! $orderId) {
            Log::warning('Razorpay webhook missing order id');

            return;
        }

        $payment = Payment::query()->where('razorpay_order_id', $orderId)->first();

        if (! $payment) {
            Log::warning('Payment not found for Razorpay capture webhook', [
                'order_id' => $orderId,
            ]);

            return;
        }

        DB::transaction(function () use ($payment, $paymentEntity): void {
            $lockedPayment = Payment::query()->where('id', $payment->id)->lockForUpdate()->first();
            if ($lockedPayment->status === Payment::STATUS_SUCCESS) {
                return;
            }

            $lockedPayment->update([
                'razorpay_payment_id' => $paymentEntity['id'] ?? null,
                'status' => Payment::STATUS_SUCCESS,
                'paid_at' => now(),
            ]);

            $user = User::query()->find($lockedPayment->user_id);
            $plan = MembershipPlan::query()->find($lockedPayment->membership_plan_id);

            if (! $user || ! $plan) {
                Log::error('Membership activation skipped due to missing user or plan', [
                    'payment_id' => $lockedPayment->id,
                    'user_id' => $lockedPayment->user_id,
                    'plan_id' => $lockedPayment->membership_plan_id,
                ]);

                return;
            }

            $this->membershipService->activateMembership($user, $plan, $lockedPayment);
        });
    }

    private function handlePaymentFailed(array $payload): void
    {
        $paymentEntity = $payload['payload']['payment']['entity'] ?? [];
        $orderId = $paymentEntity['order_id'] ?? null;

        if (! $orderId) {
            Log::warning('Razorpay webhook missing order id for failed payment');

            return;
        }

        Payment::query()
            ->where('razorpay_order_id', $orderId)
            ->update([
                'razorpay_payment_id' => $paymentEntity['id'] ?? null,
                'status' => Payment::STATUS_FAILED,
            ]);
    }
}

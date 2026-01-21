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
use Razorpay\Api\Errors\SignatureVerificationError;
use Razorpay\Api\Utility;

class RazorpayWebhookController extends Controller
{
    public function __construct(private readonly MembershipService $membershipService)
    {
    }

    public function handle(Request $request): JsonResponse
    {
        $signature = $request->header('X-Razorpay-Signature');
        $payload = $request->getContent();

        if (! $signature) {
            Log::warning('Razorpay webhook signature missing');

            return response()->json(['message' => 'Signature missing.'], 400);
        }

        try {
            Utility::verifyWebhookSignature($payload, $signature, config('razorpay.webhook_secret'));
        } catch (SignatureVerificationError $exception) {
            Log::warning('Razorpay webhook signature invalid', [
                'error' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'Invalid signature.'], 400);
        }

        $data = json_decode($payload, true);
        if (! is_array($data)) {
            Log::warning('Razorpay webhook payload invalid');

            return response()->json(['message' => 'Invalid payload.'], 400);
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
        $orderEntity = $payload['payload']['order']['entity'] ?? [];
        $orderId = $paymentEntity['order_id'] ?? null;
        $receipt = $orderEntity['receipt'] ?? ($paymentEntity['notes']['receipt'] ?? null);

        $payment = $this->findPaymentFromWebhook($orderId, $receipt);

        if (! $payment) {
            Log::warning('Payment not found for Razorpay capture webhook', [
                'order_id' => $orderId,
                'receipt' => $receipt,
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
        $orderEntity = $payload['payload']['order']['entity'] ?? [];
        $orderId = $paymentEntity['order_id'] ?? null;
        $receipt = $orderEntity['receipt'] ?? ($paymentEntity['notes']['receipt'] ?? null);

        $payment = $this->findPaymentFromWebhook($orderId, $receipt);

        if (! $payment) {
            Log::warning('Payment not found for Razorpay failed webhook', [
                'order_id' => $orderId,
                'receipt' => $receipt,
            ]);

            return;
        }

        Payment::query()->where('id', $payment->id)->update([
            'razorpay_payment_id' => $paymentEntity['id'] ?? null,
            'status' => Payment::STATUS_FAILED,
        ]);
    }

    private function findPaymentFromWebhook(?string $orderId, ?string $receipt): ?Payment
    {
        if ($orderId) {
            $payment = Payment::query()->where('razorpay_order_id', $orderId)->first();
            if ($payment) {
                return $payment;
            }
        }

        if ($receipt) {
            return Payment::query()->where('id', $receipt)->first();
        }

        return null;
    }
}

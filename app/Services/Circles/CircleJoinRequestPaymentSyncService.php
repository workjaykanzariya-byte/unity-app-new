<?php

namespace App\Services\Circles;

use App\Models\CircleJoinRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

class CircleJoinRequestPaymentSyncService
{
    public function __construct(private readonly CircleJoinRequestNotificationService $notificationService)
    {
    }

    public function markRequestPaidFromUserCircle(User $user): void
    {
        $freshUser = User::query()->find($user->id);
        if (! $freshUser) {
            Log::warning('circle join request sync skipped - user not found during refresh', ['user_id' => $user->id]);
            return;
        }

        $activeCircleId = (string) ($freshUser->active_circle_id ?? '');
        if ($activeCircleId === '') {
            Log::info('circle join request payment sync skipped - empty active_circle_id', ['user_id' => $freshUser->id]);
            return;
        }

        $this->markRequestPaid($freshUser, $activeCircleId);
    }

    public function markRequestPaid(User $user, string $circleId, $paidAt = null): void
    {
        if (trim($circleId) === '') {
            return;
        }

        $paidAtTimestamp = $paidAt ?: now();

        $joinRequest = CircleJoinRequest::query()
            ->where('user_id', $user->id)
            ->where('circle_id', $circleId)
            ->where('status', CircleJoinRequest::STATUS_PENDING_CIRCLE_FEE)
            ->latest('created_at')
            ->first();

        if (! $joinRequest) {
            Log::info('circle join request sync skipped - no matching pending request found', [
                'user_id' => $user->id,
                'circle_id' => $circleId,
                'expected_status' => CircleJoinRequest::STATUS_PENDING_CIRCLE_FEE,
            ]);

            return;
        }

        $joinRequest->forceFill([
            'status' => CircleJoinRequest::STATUS_PAID,
            'fee_marked_at' => $joinRequest->fee_marked_at ?: $paidAtTimestamp,
            'fee_paid_at' => $joinRequest->fee_paid_at ?: $paidAtTimestamp,
            'updated_at' => now(),
        ])->save();

        Log::info('circle join request synced to paid', [
            'request_id' => $joinRequest->id,
            'user_id' => $user->id,
            'circle_id' => $circleId,
            'old_status' => CircleJoinRequest::STATUS_PENDING_CIRCLE_FEE,
            'new_status' => CircleJoinRequest::STATUS_PAID,
        ]);

        try {
            $this->notificationService->sendCircleMemberConfirmedToUser($joinRequest->fresh(['user', 'circle']));
        } catch (Throwable $exception) {
            Log::warning('Circle join request paid notification failed after payment sync', [
                'circle_join_request_id' => $joinRequest->id,
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function updateUserCircleMembershipTier(User $user): void
    {
        // Intentionally no-op.
        // Circle membership truth is maintained in circle_members/circle_subscriptions.
        // Do not write circle-specific tier labels into users.membership_status enum.
    }
}

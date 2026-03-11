<?php

namespace App\Services\Circles;

use App\Models\CircleJoinRequest;
use App\Models\CircleSubscription;
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
        $activeCircleId = (string) ($user->active_circle_id ?? '');

        if ($activeCircleId === '') {
            return;
        }

        $joinRequest = CircleJoinRequest::query()
            ->where('user_id', $user->id)
            ->where('circle_id', $activeCircleId)
            ->where('status', CircleJoinRequest::STATUS_PENDING_CIRCLE_FEE)
            ->latest('created_at')
            ->first();

        if (! $joinRequest) {
            Log::info('No pending circle join request found for successful payment sync', [
                'user_id' => $user->id,
                'active_circle_id' => $activeCircleId,
            ]);

            return;
        }

        $joinRequest->forceFill([
            'status' => CircleJoinRequest::STATUS_PAID,
            'fee_marked_at' => $joinRequest->fee_marked_at ?: now(),
            'fee_paid_at' => $joinRequest->fee_paid_at ?: now(),
            'updated_at' => now(),
        ])->save();

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
        try {
            $activePaidCircleCount = CircleSubscription::query()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->where(function ($query): void {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->count();

            if ($activePaidCircleCount <= 0) {
                return;
            }

            $nextStatus = $activePaidCircleCount > 1 ? 'Multi Circle Peer' : 'Circle Peer';

            if ((string) $user->membership_status !== $nextStatus) {
                $user->forceFill(['membership_status' => $nextStatus])->save();
            }
        } catch (Throwable $exception) {
            Log::warning('Failed to sync user circle membership tier', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}

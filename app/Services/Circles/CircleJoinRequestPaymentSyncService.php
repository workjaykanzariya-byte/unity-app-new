<?php

namespace App\Services\Circles;

use App\Models\Circle;
use App\Models\CircleJoinRequest;
use App\Models\CircleSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

class CircleJoinRequestPaymentSyncService
{
    public function __construct(private readonly CircleJoinRequestService $circleJoinRequestService)
    {
    }

    public function syncSuccessfulPayment(User $user, Circle $circle, array $context = []): ?CircleJoinRequest
    {
        $pendingJoinRequest = CircleJoinRequest::query()
            ->where('user_id', $user->id)
            ->where('circle_id', $circle->id)
            ->where('status', CircleJoinRequest::STATUS_PENDING_CIRCLE_FEE)
            ->latest('created_at')
            ->first();

        if (! $pendingJoinRequest) {
            Log::info('circle payment succeeded without pending join request', [
                'user_id' => $user->id,
                'circle_id' => $circle->id,
                'context' => $context,
            ]);

            $this->updateUserCircleMembershipTier($user);

            return null;
        }

        $updated = $this->circleJoinRequestService->markPaidAndConvertToMember($pendingJoinRequest, $context);

        if (! $updated->fee_marked_at) {
            $updated->forceFill(['fee_marked_at' => now()])->save();
        }

        $this->updateUserCircleMembershipTier($user);

        return $updated->fresh(['circle', 'user']);
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

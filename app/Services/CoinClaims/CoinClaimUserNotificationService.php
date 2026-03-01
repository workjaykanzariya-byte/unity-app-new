<?php

namespace App\Services\CoinClaims;

use App\Models\CoinClaimRequest;
use App\Models\Notification;
use App\Support\CoinClaims\CoinClaimActivityRegistry;
use Illuminate\Support\Facades\Log;

class CoinClaimUserNotificationService
{
    public function __construct(private readonly CoinClaimActivityRegistry $registry)
    {
    }

    public function sendApproved(CoinClaimRequest $claim): Notification
    {
        $activity = $this->registry->get((string) $claim->activity_code) ?? [];
        $activityLabel = (string) ($activity['label'] ?? $claim->activity_code);
        $coinsAwarded = $claim->coins_awarded !== null ? (int) $claim->coins_awarded : null;

        $payload = [
            'notification_type' => 'coin_claim_approved',
            'title' => 'Coin claim approved',
            'body' => 'Your coin claim for ' . $activityLabel . ' has been approved.',
            'coin_claim_id' => (string) $claim->id,
            'activity_code' => (string) $claim->activity_code,
            'coins_awarded' => $coinsAwarded,
            'reason' => null,
            'reviewed_at' => optional($claim->reviewed_at)->toISOString(),
        ];

        return $this->store($claim, 'coin_claim_approved', $payload);
    }

    public function sendRejected(CoinClaimRequest $claim): Notification
    {
        $activity = $this->registry->get((string) $claim->activity_code) ?? [];
        $activityLabel = (string) ($activity['label'] ?? $claim->activity_code);

        $payload = [
            'notification_type' => 'coin_claim_rejected',
            'title' => 'Coin claim rejected',
            'body' => 'Your coin claim for ' . $activityLabel . ' has been rejected.',
            'coin_claim_id' => (string) $claim->id,
            'activity_code' => (string) $claim->activity_code,
            'coins_awarded' => null,
            'reason' => $claim->admin_note,
            'reviewed_at' => optional($claim->reviewed_at)->toISOString(),
        ];

        return $this->store($claim, 'coin_claim_rejected', $payload);
    }

    private function store(CoinClaimRequest $claim, string $type, array $payload): Notification
    {
        Log::info('coin_claim.notification.store', [
            'claim_id' => (string) $claim->id,
            'user_id' => (string) $claim->user_id,
            'type' => $type,
        ]);

        try {
            return Notification::create([
                'user_id' => $claim->user_id,
                'type' => $type,
                'payload' => $payload,
                'is_read' => false,
                'created_at' => now(),
                'read_at' => null,
            ]);
        } catch (\Throwable $exception) {
            Log::error('coin_claim.notification.store_failed', [
                'claim_id' => (string) $claim->id,
                'user_id' => (string) $claim->user_id,
                'type' => $type,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}

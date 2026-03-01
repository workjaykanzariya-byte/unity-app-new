<?php

namespace App\Notifications;

use App\Models\CoinClaimRequest;
use App\Support\CoinClaims\CoinClaimActivityRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class CoinClaimReviewedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly CoinClaimRequest $claim,
        private readonly string $status,
        private readonly ?int $coinsAwarded = null,
        private readonly ?string $reason = null,
    ) {
        $this->afterCommit = true;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->payload();
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->payload());
    }

    private function payload(): array
    {
        $registry = app(CoinClaimActivityRegistry::class);
        $activity = $registry->get((string) $this->claim->activity_code);

        return [
            'notification_type' => 'coin_claim_reviewed',
            'coin_claim_id' => (string) $this->claim->id,
            'claim_id' => (string) $this->claim->id,
            'activity_code' => (string) $this->claim->activity_code,
            'activity_label' => $activity['label'] ?? null,
            'decision' => $this->status,
            'status' => $this->status,
            'coins_awarded' => $this->coinsAwarded,
            'reason' => $this->status === 'rejected' ? $this->reason : null,
            'reviewed_at' => optional($this->claim->reviewed_at)->toISOString(),
        ];
    }
}

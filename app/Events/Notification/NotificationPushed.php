<?php

namespace App\Events\Notification;

use App\Models\Notification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationPushed implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public Notification $notification)
    {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('user.' . $this->notification->user_id);
    }

    public function broadcastAs(): string
    {
        return 'NotificationPushed';
    }

    public function broadcastWith(): array
    {
        $payload = $this->notification->payload ?? [];

        return [
            'notification_id' => $this->notification->id,
            'type' => $this->notification->type,
            'title' => $payload['title'] ?? null,
            'body' => $payload['body'] ?? null,
            'data' => $payload['data'] ?? $payload,
            'created_at' => $this->notification->created_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Events\User;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class UserPresenceUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public string $userId,
        public string $status,
        public Carbon $lastSeenAt,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.' . $this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'UserPresenceUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'status' => $this->status,
            'last_seen_at' => $this->lastSeenAt->toISOString(),
        ];
    }
}

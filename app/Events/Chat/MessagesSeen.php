<?php

namespace App\Events\Chat;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessagesSeen implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public string $chatId,
        public string $seenBy,
        public string $seenAt
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('chat.' . $this->chatId);
    }

    public function broadcastAs(): string
    {
        return 'MessagesSeen';
    }

    public function broadcastWith(): array
    {
        return [
            'chat_id' => $this->chatId,
            'seen_by' => $this->seenBy,
            'seen_at' => $this->seenAt,
        ];
    }
}

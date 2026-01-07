<?php

namespace App\Events\Chat;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class MessagesSeen implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public string $chatId,
        public string $seenByUserId,
        public array $messageIds,
        public Carbon $seenAt,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat.' . $this->chatId)];
    }

    public function broadcastAs(): string
    {
        return 'MessagesSeen';
    }

    public function broadcastWith(): array
    {
        return [
            'chat_id' => $this->chatId,
            'seen_by_user_id' => $this->seenByUserId,
            'message_ids' => $this->messageIds,
            'seen_at' => $this->seenAt->toISOString(),
        ];
    }
}

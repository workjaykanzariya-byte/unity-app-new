<?php

namespace App\Events\Chat;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDelivered implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public Message $message)
    {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('chat.' . $this->message->chat_id);
    }

    public function broadcastAs(): string
    {
        return 'MessageDelivered';
    }

    public function broadcastWith(): array
    {
        $sender = $this->message->sender;
        $senderId = auth()->id() ?? $sender?->id ?? $this->message->sender_id;

        return [
            'chat_id' => $this->message->chat_id,
            'message_id' => $this->message->id,
            'content' => $this->message->content,
            'sender' => [
                'id' => $senderId,
                'display_name' => $sender?->display_name,
                'profile_photo_url' => $sender?->profile_photo_url,
            ],
            'created_at' => $this->message->created_at?->toIso8601String(),
        ];
    }
}

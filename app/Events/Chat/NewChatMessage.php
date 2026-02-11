<?php

namespace App\Events\Chat;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewChatMessage implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Chat $chat,
        public Message $message
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('chat.' . $this->chat->id);
    }

    public function broadcastAs(): string
    {
        return 'chat.message.new';
    }

    public function broadcastWith(): array
    {
        return [
            'chat_id' => (string) $this->chat->id,
            'message' => [
                'id' => (string) $this->message->id,
                'chat_id' => (string) $this->message->chat_id,
                'sender_id' => (string) $this->message->sender_id,
                'content' => $this->message->content,
                'preview' => filled($this->message->content) ? $this->message->content : '📎 Attachment',
                'attachments' => $this->message->attachments ?? [],
                'is_read' => (bool) $this->message->is_read,
                'created_at' => $this->message->created_at,
            ],
        ];
    }
}

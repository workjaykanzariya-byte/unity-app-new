<?php

namespace App\Services\Realtime;

use App\Events\Chat\MessageDelivered;
use App\Events\Chat\MessagesSeen;
use App\Events\Chat\UserTyping;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Carbon;

class ChatRealtimeService
{
    public function broadcastMessageDelivered(Chat $chat, Message $message, User $fromUser): void
    {
        $receiverId = $chat->user1_id === $fromUser->id ? $chat->user2_id : $chat->user1_id;

        event(new MessageDelivered(
            $chat->id,
            [
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'receiver_id' => $receiverId,
                'body' => $message->content,
                'created_at' => optional($message->created_at)->toISOString(),
            ],
            [
                'id' => $fromUser->id,
                'display_name' => $fromUser->display_name,
                'profile_photo_url' => $fromUser->profile_photo_url,
            ],
        ));
    }

    public function broadcastMessagesSeen(Chat $chat, string $userId, array $messageIds, ?Carbon $seenAt = null): void
    {
        event(new MessagesSeen(
            $chat->id,
            $userId,
            $messageIds,
            $seenAt ?? now(),
        ));
    }

    public function broadcastTyping(Chat $chat, string $userId, bool $isTyping): void
    {
        event(new UserTyping($chat->id, $userId, $isTyping));
    }
}

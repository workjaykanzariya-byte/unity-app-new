<?php

namespace App\Services\Notifications;

use App\Events\Notification\NotificationPushed;
use App\Http\Resources\NotificationResource;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use App\Support\NotificationTypes;
use Illuminate\Support\Str;

class NotificationService
{
    public function createChatMessageNotification(User $receiverUser, Chat $chat, Message $message, User $fromUser): Notification
    {
        $notification = Notification::create([
            'user_id' => $receiverUser->id,
            'type' => NotificationTypes::normalize(NotificationTypes::CHAT_MESSAGE),
            'payload' => [
                'chat_id' => $chat->id,
                'message_id' => $message->id,
                'from_user' => [
                    'id' => $fromUser->id,
                    'display_name' => $fromUser->display_name,
                    'profile_photo_url' => $fromUser->profile_photo_url,
                ],
                'preview' => Str::limit((string) $message->content, 120, '...'),
                'created_at' => optional($message->created_at)->toISOString(),
            ],
            'is_read' => false,
            'read_at' => null,
        ]);

        event(new NotificationPushed(
            $receiverUser->id,
            NotificationResource::make($notification)->resolve(),
        ));

        return $notification;
    }
}

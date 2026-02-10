<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ChatResource extends JsonResource
{
    public function toArray($request): array
    {
        $authUser = $request->user();

        $otherUser = null;
        if ($authUser) {
            if ($authUser->id === $this->user1_id) {
                $otherUser = $this->user2;
            } elseif ($authUser->id === $this->user2_id) {
                $otherUser = $this->user1;
            }
        }

        $lastMessage = $this->whenLoaded('lastMessage', function () {
            return [
                'id' => $this->lastMessage->id,
                'sender_id' => $this->lastMessage->sender_id,
                'content' => $this->lastMessage->content,
                'snippet' => Str::limit((string) $this->lastMessage->content, 120),
                'attachments' => $this->lastMessage->attachments,
                'is_read' => (bool) $this->lastMessage->is_read,
                'created_at' => $this->lastMessage->created_at,
            ];
        });

        $unreadCount = null;
        if (isset($this->unread_count)) {
            $unreadCount = (int) $this->unread_count;
        }

        return [
            'id' => $this->id,
            'user1_id' => $this->user1_id,
            'user2_id' => $this->user2_id,
            'last_message_at' => $this->last_message_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'other_user' => $otherUser ? [
                'id' => $otherUser->id,
                'display_name' => $otherUser->display_name,
                'avatar_url' => $otherUser->profile_photo_url,
            ] : null,
            'last_message' => $lastMessage,
            'unread_count' => $unreadCount,
        ];
    }
}

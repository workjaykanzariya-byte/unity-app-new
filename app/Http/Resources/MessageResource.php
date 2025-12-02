<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray($request): array
    {
        if (! $this->resource) {
            return null;
        }

        return [
            'id' => (string) $this->id,
            'chat_id' => (string) $this->chat_id,
            'sender_id' => (string) $this->sender_id,
            'content' => $this->content,
            'attachments' => $this->attachments,
            'is_read' => (bool) $this->is_read,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'sender' => $this->whenLoaded('sender', function () {
                return [
                    'id' => (string) $this->sender->id,
                    'display_name' => $this->sender->display_name,
                    'first_name' => $this->sender->first_name,
                    'last_name' => $this->sender->last_name,
                    'profile_photo_url' => $this->sender->profile_photo_url,
                ];
            }),
        ];
    }
}

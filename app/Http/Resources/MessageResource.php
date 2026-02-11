<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

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
            'preview' => $this->preview(),
            'kind' => $this->kind ?? null,
            'attachments' => $this->attachments ?? [],
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

    private function preview(): string
    {
        if (filled($this->content)) {
            return Str::limit((string) $this->content, 120, '');
        }

        if (is_array($this->attachments) && isset($this->attachments[0]['name']) && filled($this->attachments[0]['name'])) {
            return '📎 ' . (string) $this->attachments[0]['name'];
        }

        return '📎 Attachment';
    }
}

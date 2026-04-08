<?php

namespace App\Http\Resources\Leadership;

use Illuminate\Http\Resources\Json\JsonResource;

class LeadershipMessageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'circle_id' => $this->circle_id,
            'message_type' => $this->message_type,
            'message_text' => $this->message_text,
            'created_at' => $this->created_at,
            'sender' => [
                'id' => optional($this->sender)->id,
                'display_name' => optional($this->sender)->display_name,
                'profile_photo_url' => optional($this->sender)->profile_photo_url,
            ],
        ];
    }
}

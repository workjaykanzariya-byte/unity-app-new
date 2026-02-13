<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class CircleMemberResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'user' => [
                'id' => $this->user?->id,
                'display_name' => $this->user?->display_name,
                'first_name' => $this->user?->first_name,
                'last_name' => $this->user?->last_name,
                'avatar_url' => $this->user?->profile_photo_url,
            ],
            'role' => $this->role,
            'status' => $this->status,
            'joined_at' => $this->joined_at,
        ];
    }
}

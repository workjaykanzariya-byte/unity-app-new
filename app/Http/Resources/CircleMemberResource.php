<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CircleMemberResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role,
            'status' => $this->status,
            'substitute_count' => $this->substitute_count,
            'joined_at' => $this->joined_at,
            'left_at' => $this->left_at,
            'created_at' => $this->created_at,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'display_name' => $this->user->display_name,
                    'first_name' => $this->user->first_name,
                    'last_name' => $this->user->last_name,
                    'profile_photo_url' => $this->user->profile_photo_url,
                    'membership_status' => $this->user->membership_status,
                ];
            }),
        ];
    }
}

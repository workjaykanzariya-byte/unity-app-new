<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CircleMemberResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'circle_id' => $this->circle_id,
            'role' => $this->role,
            'status' => $this->status,
            'joined_at' => $this->joined_at,
            'left_at' => $this->left_at,
            'substitute_count' => $this->substitute_count,
            'role_id' => $this->role_id,

            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),

            'role_details' => $this->whenLoaded('roleModel', function () {
                return [
                    'id' => $this->roleModel->id,
                    'name' => $this->roleModel->name ?? null,
                    'slug' => $this->roleModel->slug ?? null,
                ];
            }),
        ];
    }
}

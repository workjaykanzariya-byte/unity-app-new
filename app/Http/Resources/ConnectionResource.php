<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConnectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'is_approved' => (bool) $this->is_approved,
            'approved_at' => $this->approved_at,
            'created_at' => $this->created_at,
            'requester' => [
                'id' => $this->requester?->id,
                'display_name' => $this->requester?->display_name,
                'first_name' => $this->requester?->first_name,
                'last_name' => $this->requester?->last_name,
                'profile_photo_url' => $this->requester?->profile_photo_url,
            ],
            'addressee' => [
                'id' => $this->addressee?->id,
                'display_name' => $this->addressee?->display_name,
                'first_name' => $this->addressee?->first_name,
                'last_name' => $this->addressee?->last_name,
                'profile_photo_url' => $this->addressee?->profile_photo_url,
            ],
        ];
    }
}

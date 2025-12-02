<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SupportRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (string) $this->id,
            'user_id' => (string) $this->user_id,
            'support_type' => $this->support_type,
            'details' => $this->details,
            'attachments' => $this->attachments,
            'routed_to_user_id' => $this->routed_to_user_id ? (string) $this->routed_to_user_id : null,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => (string) $this->user->id,
                    'display_name' => $this->user->display_name,
                    'first_name' => $this->user->first_name,
                    'last_name' => $this->user->last_name,
                    'profile_photo_url' => $this->user->profile_photo_url,
                ];
            }),

            'routed_to_user' => $this->whenLoaded('routedToUser', function () {
                return [
                    'id' => (string) $this->routedToUser->id,
                    'display_name' => $this->routedToUser->display_name,
                    'first_name' => $this->routedToUser->first_name,
                    'last_name' => $this->routedToUser->last_name,
                    'profile_photo_url' => $this->routedToUser->profile_photo_url,
                ];
            }),
        ];
    }
}

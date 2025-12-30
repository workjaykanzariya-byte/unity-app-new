<?php

namespace App\Http\Resources\Connection;

use Illuminate\Http\Resources\Json\JsonResource;

class ConnectionUserResource extends JsonResource
{
    public function toArray($request): array
    {
        $fileId = $this->profile_photo_file_id ?? $this->profile_photo_id ?? null;

        return [
            'id' => $this->id,
            'display_name' => $this->display_name,
            'profile_photo_url' => $fileId ? url('/api/v1/files/' . $fileId) : null,
            'city' => $this->city,
            'membership_status' => $this->membership_status,
        ];
    }
}

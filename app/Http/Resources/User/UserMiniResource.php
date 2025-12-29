<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class UserMiniResource extends JsonResource
{
    public function toArray($request): array
    {
        $profilePhotoUrl = $this->profile_photo_url
            ?? ($this->profilePhotoFile ? url('/api/v1/files/' . $this->profilePhotoFile->id) : null);

        return [
            'id' => $this->id,
            'display_name' => $this->display_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'profile_photo_url' => $profilePhotoUrl,
        ];
    }
}

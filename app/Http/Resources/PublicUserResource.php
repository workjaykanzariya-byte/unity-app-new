<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $name = $this->display_name
            ?? trim((string) ($this->first_name ?? '') . ' ' . (string) ($this->last_name ?? ''));

        $profileImageUrl = $this->profile_photo_url;

        if (! $profileImageUrl && ! empty($this->profile_photo_file_id)) {
            $profileImageUrl = url('/api/v1/files/' . $this->profile_photo_file_id);
        }

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'name' => $name !== '' ? $name : null,
            'email' => $this->email,
            'phone' => $this->phone,
            'profile_slug' => $this->public_profile_slug,
            'company_name' => $this->company_name,
            'designation' => $this->designation,
            'city_id' => $this->city_id,
            'city_name' => optional($this->whenLoaded('city'))->name,
            'country' => $this->country,
            'profile_image_url' => $profileImageUrl,
            'membership_status' => $this->membership_status,
            'created_at' => $this->created_at,
        ];
    }
}

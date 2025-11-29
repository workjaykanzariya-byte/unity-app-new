<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'phone' => $this->phone,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'display_name' => $this->display_name,
            'designation' => $this->designation,
            'company_name' => $this->company_name,
            'profile_photo_url' => $this->profile_photo_url,
            'short_bio' => $this->short_bio,
            'long_bio_html' => $this->long_bio_html,
            'membership_status' => $this->membership_status,
            'membership_expiry' => $this->membership_expiry,
            'coins_balance' => $this->coins_balance,
            'city' => new CityResource($this->whenLoaded('city')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

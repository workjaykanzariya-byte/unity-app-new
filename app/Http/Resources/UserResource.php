<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'public_profile_slug' => $this->public_profile_slug,
            'first_name'          => $this->first_name,
            'last_name'           => $this->last_name,
            'display_name'        => $this->display_name,
            'company_name'        => $this->company_name,
            'email'               => $this->email,
            'phone'               => $this->phone,
            'city'                => new CityResource($this->whenLoaded('city')),
            'membership_status'   => $this->membership_status,
            'coins_balance'       => $this->coins_balance,
            'profile_photo_url'   => $this->profile_photo_url,
            'business_type'       => $this->business_type,
            'last_login_at'       => $this->last_login_at,
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}

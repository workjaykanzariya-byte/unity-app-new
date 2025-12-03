<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserFullResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'display_name' => $this->display_name,
            'email' => $this->email,
            'phone' => $this->phone,

            'company_name' => $this->company_name,
            'designation' => $this->designation,
            'about' => $this->about,
            'gender' => $this->gender,
            'dob' => $this->dob,

            'skills' => $this->skills,
            'interests' => $this->interests,

            'city' => new CityResource($this->whenLoaded('city')),

            'profile_photo_url' => $this->profile_photo_url,
            'cover_photo_url' => $this->cover_photo_url,

            'social_links' => $this->social_links,

            'membership_status' => $this->membership_status,
            'coins_balance' => $this->coins_balance,
            'influencer_stars' => $this->influencer_stars,
            'public_profile_slug' => $this->public_profile_slug,
        ];
    }
}

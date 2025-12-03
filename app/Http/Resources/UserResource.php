<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'public_profile_slug' => $this->public_profile_slug,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'display_name' => $this->display_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'city' => new CityResource($this->whenLoaded('city')),
            'company_name' => $this->company_name,
            'designation' => $this->designation,
            'about' => $this->short_bio,
            'gender' => $this->gender,
            'dob' => $this->dob,
            'experience_years' => $this->experience_years,
            'experience_summary' => $this->experience_summary,
            'skills' => $this->skills,
            'interests' => $this->interests,
            'social_links' => $this->social_links,
            'profile_photo_file_id' => $this->profile_photo_file_id,
            'cover_photo_file_id' => $this->cover_photo_file_id,
            'membership_status' => $this->membership_status,
            'coins_balance' => $this->coins_balance,
            'last_login_at' => $this->last_login_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

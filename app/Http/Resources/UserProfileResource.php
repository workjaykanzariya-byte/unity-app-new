<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    public function toArray($request): array
    {
        $skills    = is_array($this->skills) ? $this->skills : [];
        $interests = is_array($this->interests) ? $this->interests : [];

        $links = $this->social_links ?? [];
        if (!is_array($links)) {
            $links = [];
        }

        return [
            'id'                 => $this->id,
            'first_name'         => $this->first_name,
            'last_name'          => $this->last_name,
            'display_name'       => $this->display_name,
            'email'              => $this->email,
            'phone'              => $this->phone,

            'company_name'       => $this->company_name,
            'designation'        => $this->designation,

            // DB: short_bio -> API field "about"
            'about'              => $this->short_bio,

            'gender'             => $this->gender,
            'dob'                => optional($this->dob)?->format('Y-m-d'),

            'experience_years'   => $this->experience_years,
            'experience_summary' => $this->experience_summary,

            'city'               => $this->city,
            'city_id'            => $this->city_id,

            'skills'             => $skills,
            'interests'          => $interests,

            'social_links'       => [
                'linkedin'  => $links['linkedin']  ?? null,
                'facebook'  => $links['facebook']  ?? null,
                'instagram' => $links['instagram'] ?? null,
                'website'   => $links['website']   ?? null,
            ],

            'profile_photo_id'   => $this->profile_photo_file_id,
            'cover_photo_id'     => $this->cover_photo_file_id,

            'profile_photo_url'  => optional($this->profilePhotoFile)->public_url ?? null,
            'cover_photo_url'    => optional($this->coverPhotoFile)->public_url ?? null,

            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        // Normalise skills & interests
        $skills = is_array($this->skills) ? $this->skills : [];
        $interests = is_array($this->interests) ? $this->interests : [];

        // Normalise social_links into an object with fixed keys
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

            // DB: short_bio → API: about
            'about'              => $this->short_bio,

            'gender'             => $this->gender,
            'dob'                => optional($this->dob)?->format('Y-m-d'),

            'experience_years'   => $this->experience_years,
            'experience_summary' => $this->experience_summary,

            // city_id (fk) + city_name (text column)
            'city_id'            => $this->city_id,
            'city_name'          => $this->city, // <-- text field from users table

            // city object from relation (if loaded)
            'city'               => $this->whenLoaded('city', function () {
                return [
                    'id'      => $this->city->id,
                    'name'    => $this->city->name,
                    'state'   => $this->city->state_name,
                    'country' => $this->city->country_name,
                ];
            }),

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

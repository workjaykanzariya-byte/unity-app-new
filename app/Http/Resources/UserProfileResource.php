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
        return [
            'id'                 => $this->id,
            'first_name'         => $this->first_name,
            'last_name'          => $this->last_name,
            'display_name'       => $this->display_name,
            'email'              => $this->email,
            'phone'              => $this->phone,

            'company_name'       => $this->company_name,
            'designation'        => $this->designation,

            // DB column short_bio is exposed as "about" in API
            'about'              => $this->short_bio,

            'gender'             => $this->gender,
            'dob'                => optional($this->dob)?->format('Y-m-d'),

            'experience_years'   => $this->experience_years,
            'experience_summary' => $this->experience_summary,

            // city_id is editable; city relation is optional/read-only
            'city_id'            => $this->city_id,
            'city'               => $this->whenLoaded('city', function () {
                return [
                    'id'      => $this->city->id,
                    'name'    => $this->city->name,
                    'state'   => $this->city->state_name,
                    'country' => $this->city->country_name,
                ];
            }),

            'skills'             => $this->skills ?? [],
            'interests'          => $this->interests ?? [],

            // JSON object with social links
            'social_links'       => $this->social_links ?? [
                'linkedin'  => null,
                'facebook'  => null,
                'instagram' => null,
                'website'   => null,
            ],

            'profile_photo_id'   => $this->profile_photo_file_id,
            'cover_photo_id'     => $this->cover_photo_file_id,

            // URLs built from related File models if they are loaded
            'profile_photo_url'  => optional($this->profilePhotoFile)->public_url ?? null,
            'cover_photo_url'    => optional($this->coverPhotoFile)->public_url ?? null,

            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at,
        ];
    }
}

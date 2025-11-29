<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CircleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'purpose' => $this->purpose,
            'announcement' => $this->announcement,
            'status' => $this->status,
            'referral_score' => $this->referral_score,
            'visitor_count' => $this->visitor_count,
            'industry_tags' => $this->industry_tags,
            'calendar' => $this->calendar,
            'city' => $this->whenLoaded('city', function () {
                return [
                    'id' => $this->city->id,
                    'name' => $this->city->name,
                    'state' => $this->city->state,
                    'country' => $this->city->country,
                ];
            }),
            'founder' => $this->whenLoaded('founder', function () {
                return [
                    'id' => $this->founder->id,
                    'display_name' => $this->founder->display_name,
                    'first_name' => $this->founder->first_name,
                    'last_name' => $this->founder->last_name,
                    'profile_photo_url' => $this->founder->profile_photo_url,
                ];
            }),
            'template_id' => $this->template_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

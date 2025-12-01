<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CircleResource extends JsonResource
{
    public function toArray($request): array
    {
        $founder = $this->whenLoaded('founder');
        $city = $this->whenLoaded('city');
        $currentMember = $this->whenLoaded('currentMember');

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
            'city' => $city ? [
                'id' => $city->id,
                'name' => $city->name,
            ] : null,
            'founder' => $founder ? [
                'id' => $founder->id,
                'display_name' => $founder->display_name,
                'first_name' => $founder->first_name,
                'last_name' => $founder->last_name,
                'profile_photo_url' => $founder->profile_photo_url,
            ] : null,
            'members_count' => $this->members_count ?? null,
            'is_member' => $currentMember ? true : false,
            'member_status' => $currentMember->status ?? null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

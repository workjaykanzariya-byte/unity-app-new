<?php

namespace App\Http\Resources\Requirement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequirementInterestUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->whenLoaded('user');

        return [
            'user_id' => $this->user_id,
            'name' => $user?->display_name ?: trim(($user?->first_name ?? '') . ' ' . ($user?->last_name ?? '')),
            'company' => $user?->company_name,
            'city' => $user?->city,
            'profile_photo_url' => $user?->profile_photo_url,
            'source' => $this->source,
            'comment' => $this->comment,
            'created_at' => optional($this->created_at)?->toISOString(),
        ];
    }
}

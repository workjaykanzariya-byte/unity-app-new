<?php

namespace App\Http\Resources\Requirement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequirementTimelineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $creator = $this->whenLoaded('user');

        return [
            'id' => $this->id,
            'user_name' => $creator?->display_name ?: trim(($creator?->first_name ?? '') . ' ' . ($creator?->last_name ?? '')),
            'company' => $creator?->company_name,
            'city' => $creator?->city,
            'category' => data_get($this->category_filter, 'category') ?? data_get($this->category_filter, '0'),
            'profile_photo_url' => $creator?->profile_photo_url,
            'subject' => $this->subject,
            'description' => $this->description,
            'media' => collect($this->media ?? [])->map(function ($item) {
                if (is_string($item)) {
                    return ['id' => null, 'type' => 'unknown', 'url' => $item];
                }

                $id = data_get($item, 'id');

                return [
                    'id' => $id,
                    'type' => data_get($item, 'type', 'image'),
                    'url' => data_get($item, 'url') ?: ($id ? url('/api/v1/files/' . $id) : null),
                ];
            })->values()->all(),
            'region_filter' => $this->region_filter,
            'category_filter' => $this->category_filter,
            'status' => $this->status,
            'created_at' => optional($this->created_at)?->toISOString(),
        ];
    }
}

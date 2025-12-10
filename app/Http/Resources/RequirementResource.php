<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequirementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'subject' => $this->subject,
            'description' => $this->description,
            'media' => $this->media ?? [],
            'region_filter' => $this->region_filter,
            'category_filter' => $this->category_filter,
            'region_label' => $this->region_label ?? ($this->region_filter['region_label'] ?? null),
            'city_name' => $this->city_name ?? ($this->region_filter['city_name'] ?? null),
            'category' => $this->category ?? ($this->category_filter['category'] ?? null),
            'budget' => $this->budget,
            'timeline' => $this->timeline,
            'tags' => $this->tags ?? [],
            'visibility' => $this->visibility,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

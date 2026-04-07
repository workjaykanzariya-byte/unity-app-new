<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CircleCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $childrenRelation = $this->relationLoaded('childrenRecursive')
            ? 'childrenRecursive'
            : ($this->relationLoaded('children') ? 'children' : null);

        $children = $childrenRelation
            ? CircleCategoryResource::collection($this->{$childrenRelation})->resolve()
            : [];

        $childrenCount = $this->children_count ?? count($children);

        return [
            'id' => $this->id,
            'name' => $this->category_name,
            'category_name' => $this->category_name,
            'slug' => $this->slug ?? null,
            'level' => $this->level ?? null,
            'parent_id' => $this->parent_id ?? null,
            'has_children' => $childrenCount > 0,
            'children_count' => $childrenCount,
            'children' => $children,
        ];
    }
}

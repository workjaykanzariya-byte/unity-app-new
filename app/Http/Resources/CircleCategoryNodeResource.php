<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CircleCategoryNodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $childrenRelation = $this->relationLoaded('childrenRecursive')
            ? 'childrenRecursive'
            : ($this->relationLoaded('children') ? 'children' : null);

        $children = $childrenRelation
            ? CircleCategoryNodeResource::collection($this->{$childrenRelation})->resolve()
            : [];

        $payload = [
            'id' => $this->id,
            'name' => $this->category_name,
            'level' => $this->level !== null ? (int) $this->level : null,
        ];

        if ($children !== []) {
            $payload['children'] = $children;
        }

        return $payload;
    }
}

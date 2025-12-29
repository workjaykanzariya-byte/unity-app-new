<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class PostLikeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'liked_at' => $this->created_at,
            'user' => new UserMiniResource($this->whenLoaded('user')),
        ];
    }
}

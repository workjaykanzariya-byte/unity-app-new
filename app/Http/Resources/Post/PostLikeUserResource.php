<?php

namespace App\Http\Resources\Post;

use App\Http\Resources\User\UserMiniResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PostLikeUserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'user' => new UserMiniResource($this->whenLoaded('user')),
            'liked_at' => $this->created_at,
        ];
    }
}

<?php

namespace App\Http\Resources\Post;

use App\Http\Resources\User\UserMiniResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PostCommentMiniResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'created_at' => $this->created_at,
            'user' => new UserMiniResource($this->whenLoaded('user')),
        ];
    }
}

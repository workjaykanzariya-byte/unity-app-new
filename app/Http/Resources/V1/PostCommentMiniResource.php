<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class PostCommentMiniResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->content,
            'created_at' => $this->created_at,
            'user' => new UserMiniResource($this->whenLoaded('user')),
        ];
    }
}

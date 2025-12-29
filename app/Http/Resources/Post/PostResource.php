<?php

namespace App\Http\Resources\Post;

use App\Http\Resources\Post\PostCommentMiniResource;
use App\Http\Resources\Post\PostMediaResource;
use App\Http\Resources\User\UserMiniResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray($request): array
    {
        $mediaItems = collect($this->media_items ?? $this->media ?? [])->values();

        return [
            'id' => $this->id,
            'content' => $this->content_text,
            'visibility' => $this->visibility,
            'created_at' => $this->created_at,
            'author' => new UserMiniResource($this->whenLoaded('author')),
            'media' => PostMediaResource::collection($mediaItems),
            'likes_count' => (int) ($this->likes_count ?? 0),
            'comments_count' => (int) ($this->comments_count ?? 0),
            'liked_by_me' => (bool) ($this->liked_by_me ?? false),
            'latest_comments' => $this->when(
                $this->relationLoaded('latest_comments'),
                fn () => PostCommentMiniResource::collection($this->latest_comments)
            ),
        ];
    }
}

<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray($request): array
    {
        $authUser = auth()->user();

        $isSaved = false;

        if ($authUser) {
            if (isset($this->is_saved_by_me)) {
                $isSaved = (bool) $this->is_saved_by_me;
            } elseif ($this->relationLoaded('saves')) {
                $isSaved = $this->saves->contains('user_id', $authUser->id);
            }
        }

        $savesCount = isset($this->saves_count)
            ? (int) $this->saves_count
            : ($this->relationLoaded('saves') ? $this->saves->count() : 0);

        return [
            'id' => $this->id,
            'caption' => $this->content_text,
            'content' => $this->content_text,
            'visibility' => $this->visibility,
            'created_at' => $this->created_at,
            'author' => new UserMiniResource($this->whenLoaded('author')),
            'media' => PostMediaResource::collection(collect($this->media ?? [])),
            'likes_count' => (int) ($this->likes_count ?? 0),
            'comments_count' => (int) ($this->comments_count ?? 0),
            'liked_by_me' => (bool) ($this->liked_by_me ?? $this->is_liked_by_me ?? false),
            'saves_count' => $savesCount,
            'is_saved' => $isSaved,
            'latest_comments' => PostCommentMiniResource::collection($this->whenLoaded('comments')),
        ];
    }
}

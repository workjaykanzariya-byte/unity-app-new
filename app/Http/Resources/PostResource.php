<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $this->whenLoaded('author');

        $currentUser = $request->user();

        return [
            'id' => $this->id,
            'content' => $this->content_text,
            'author' => $user ? [
                'id' => $user->id,
                'display_name' => $user->display_name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'profile_photo_url' => $user->profile_photo_url,
            ] : null,
            'likes_count' => $this->likes_count ?? 0,
            'comments_count' => $this->comments_count ?? 0,
            'created_at' => $this->created_at,
            'is_liked_by_me' => (bool) ($currentUser
                ? $this->whenLoaded('likes', function () use ($currentUser) {
                    return $this->likes->contains('user_id', $currentUser->id);
                }, false)
                : false),
        ];
    }
}

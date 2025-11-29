<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray($request)
    {
        $authUser = $request->user();

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'circle_id' => $this->circle_id,
            'content_text' => $this->content_text,
            'media' => $this->media,
            'tags' => $this->tags,
            'visibility' => $this->visibility,
            'moderation_status' => $this->moderation_status,
            'sponsored' => (bool) $this->sponsored,
            'is_deleted' => (bool) $this->is_deleted,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'display_name' => $this->user->display_name,
                    'first_name' => $this->user->first_name,
                    'last_name' => $this->user->last_name,
                    'profile_photo_url' => $this->user->profile_photo_url,
                ];
            }),
            'circle' => $this->whenLoaded('circle', function () {
                return [
                    'id' => $this->circle->id,
                    'name' => $this->circle->name,
                    'slug' => $this->circle->slug,
                ];
            }),
            'like_count' => $this->when(isset($this->likes_count), (int) $this->likes_count),
            'comment_count' => $this->when(isset($this->comments_count), (int) $this->comments_count),
            'is_liked_by_me' => $this->when($authUser, function () use ($authUser) {
                return $this->likes()
                    ->where('user_id', $authUser->id)
                    ->exists();
            }),
        ];
    }
}

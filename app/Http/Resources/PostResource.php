<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,

            'content'        => $this->content,
            'media'          => FileResource::collection($this->whenLoaded('media')),
            'tags'           => $this->tags,
            'visibility'     => $this->visibility,
            'moderation_status' => $this->moderation_status ?? null,

            'author' => $this->when(
                $this->relationLoaded('user') && $this->user,
                function () {
                    return [
                        'id'               => $this->user->id,
                        'display_name'     => $this->user->display_name,
                        'first_name'       => $this->user->first_name,
                        'last_name'        => $this->user->last_name,
                        'profile_photo_url'=> $this->user->profile_photo_url,
                    ];
                }
            ),

            'circle' => $this->when(
                $this->relationLoaded('circle') && $this->circle,
                function () {
                    return [
                        'id'   => $this->circle->id,
                        'name' => $this->circle->name,
                    ];
                }
            ),

            'likes_count'    => isset($this->likes_count) ? (int) $this->likes_count : 0,
            'comments_count' => isset($this->comments_count) ? (int) $this->comments_count : 0,

            'is_liked_by_me' => (bool) ($this->is_liked_by_me ?? false),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

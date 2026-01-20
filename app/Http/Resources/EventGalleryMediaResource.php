<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EventGalleryMediaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'media_type' => $this->media_type,
            'url' => $this->url,
            'thumbnail_url' => $this->thumbnail_url,
            'caption' => $this->caption,
            'created_at' => $this->created_at,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uploader_user_id' => $this->uploader_user_id,
            'disk' => $this->disk,
            'path' => $this->path ?? $this->s3_key,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size ?? $this->size_bytes,
            'width' => $this->width,
            'height' => $this->height,
            'duration' => $this->duration,
            'created_at' => $this->created_at,
            'url' => $this->url,
            'download_url' => route('files.show', $this->id),
        ];
    }
}

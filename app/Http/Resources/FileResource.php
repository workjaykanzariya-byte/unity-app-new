<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
{
    public function toArray($request): array
    {
        $disk = $this->disk ?? config('filesystems.default', 'uploads');
        $url = $this->id ? route('api.files.show', ['id' => $this->id]) : null;

        return [
            'id' => $this->id,
            'uploader_user_id' => $this->uploader_user_id,
            'disk' => $disk,
            'path' => $this->path,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'width' => $this->width,
            'height' => $this->height,
            'duration' => $this->duration,
            'original_name' => $this->original_name,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'url' => $url,
        ];
    }
}

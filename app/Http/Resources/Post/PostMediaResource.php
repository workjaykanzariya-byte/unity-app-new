<?php

namespace App\Http\Resources\Post;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PostMediaResource extends JsonResource
{
    public function toArray($request): array
    {
        $fileId = $this->resource['file_id'] ?? $this->resource['id'] ?? null;

        $fileMap = $request->attributes->get('post_media_files');

        if ($fileMap instanceof Collection) {
            $file = $fileMap->get($fileId);
        } elseif (is_array($fileMap) && array_key_exists($fileId, $fileMap)) {
            $file = $fileMap[$fileId];
        } else {
            $file = null;
        }

        return [
            'id' => $this->resource['id'] ?? $fileId,
            'type' => $this->resource['type'] ?? null,
            'file_id' => $fileId,
            'url' => $fileId ? url("/api/v1/files/{$fileId}") : null,
            'mime' => $file->mime_type ?? null,
            'width' => $file->width ?? null,
            'height' => $file->height ?? null,
        ];
    }
}

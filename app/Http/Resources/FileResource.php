<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class FileResource extends JsonResource
{
    public function toArray($request): array
    {
        $url = null;

        if ($this->s3_key) {
            $url = Storage::url($this->s3_key);
        }

        $meta = is_array($this->meta) ? $this->meta : [];

        return [
            'id' => $this->id,
            'uploader_user_id' => $this->uploader_user_id,
            's3_key' => $this->s3_key,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'width' => $this->width,
            'height' => $this->height,
            'duration' => $this->duration,
            'created_at' => $this->created_at,
            'url' => $url,
            'variants' => $this->variantUrls($meta),
            'processing_status' => $meta['processing_status'] ?? 'completed',
            'processing_error' => $meta['processing_error'] ?? null,
        ];
    }

    private function variantUrls(array $meta): array
    {
        $variants = $meta['variants'] ?? [];
        $resolved = [];

        foreach ($variants as $key => $path) {
            if (! $path) {
                $resolved[$key] = null;
                continue;
            }

            $resolved[$key] = [
                'path' => $path,
                'url' => Storage::url($path),
            ];
        }

        return $resolved;
    }
}

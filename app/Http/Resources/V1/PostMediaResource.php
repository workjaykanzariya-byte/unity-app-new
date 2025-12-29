<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class PostMediaResource extends JsonResource
{
    public function toArray($request): array
    {
        $id = data_get($this->resource, 'id');
        $fileId = data_get($this->resource, 'file_id') ?? $id;

        return [
            'id' => $id,
            'type' => data_get($this->resource, 'type'),
            'file_id' => $fileId,
            'url' => $fileId ? url("/api/v1/files/{$fileId}") : null,
        ];
    }
}

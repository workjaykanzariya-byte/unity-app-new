<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TableRowResource extends JsonResource
{
    public function toArray($request): array
    {
        if (is_object($this->resource) && method_exists($this->resource, 'getAttributes')) {
            return $this->resource->getAttributes();
        }

        return [];
    }
}

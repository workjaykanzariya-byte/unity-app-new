<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TableRowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $resource = $this->resource;

        if (is_object($resource) && method_exists($resource, 'getAttributes')) {
            return $resource->getAttributes();
        }

        return (array) $resource;
    }
}

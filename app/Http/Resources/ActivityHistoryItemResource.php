<?php

namespace App\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class ActivityHistoryItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        $data = $this->resource instanceof Model
            ? $this->resource->attributesToArray()
            : (array) $this->resource;

        $relations = [
            'initiator',
            'peer',
            'fromUser',
            'toUser',
            'user',
            'creator',
            'receiver',
            'member',
            'recipient',
            'sender',
            'owner',
            'targetUser',
            'createdBy',
            'givenBy',
            'receivedBy',
        ];

        foreach ($relations as $relation) {
            if (! $this->resource instanceof Model) {
                continue;
            }

            if (! method_exists($this->resource, $relation)) {
                continue;
            }

            try {
                $related = $this->resource->{$relation};
            } catch (\Throwable $e) {
                continue;
            }

            $data[$relation] = $this->transformRelated($related);
        }

        return $data;
    }

    protected function transformRelated($related): mixed
    {
        if ($related instanceof Model) {
            return $related->attributesToArray();
        }

        if ($related instanceof Collection) {
            return $related->map(fn ($item) => $item instanceof Model ? $item->attributesToArray() : $item)->all();
        }

        return $related;
    }
}

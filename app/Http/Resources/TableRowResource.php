<?php

namespace App\Http\Resources;

use App\Support\ActivityHistory\OtherUserProfilePhotoUrlResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TableRowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $attributes = $this->extractAttributes();

        if ($this->hasOtherUserContext($attributes)) {
            $photoResolver = app(OtherUserProfilePhotoUrlResolver::class);
            $attributes['other_user_profile_photo_url'] = $photoResolver->resolve($request->user(), $this->resource);
        }

        return $attributes;
    }

    private function extractAttributes(): array
    {
        if (is_object($this->resource) && method_exists($this->resource, 'getAttributes')) {
            return $this->resource->getAttributes();
        }

        return (array) $this->resource;
    }

    private function hasOtherUserContext(array $attributes): bool
    {
        if (array_key_exists('initiator_user_id', $attributes) && array_key_exists('peer_user_id', $attributes)) {
            return true;
        }

        return array_key_exists('from_user_id', $attributes) && array_key_exists('to_user_id', $attributes);
    }
}

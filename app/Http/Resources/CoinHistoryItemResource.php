<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CoinHistoryItemResource extends JsonResource
{
    public function toArray($request)
    {
        $relatedUser = $this->resource['related_user'] ?? null;

        return [
            'id' => $this->resource['id'] ?? null,
            'coins_delta' => (int) ($this->resource['coins_delta'] ?? 0),
            'reason_key' => $this->resource['reason_key'] ?? null,
            'reason_label' => $this->resource['reason_label'] ?? null,
            'activity_type' => $this->resource['activity_type'] ?? null,
            'activity_id' => $this->resource['activity_id'] ?? null,
            'activity_title' => $this->resource['activity_title'] ?? null,
            'related_user' => $relatedUser ? [
                'id' => $relatedUser['id'] ?? null,
                'display_name' => $relatedUser['display_name'] ?? null,
                'profile_photo_url' => $relatedUser['profile_photo_url'] ?? null,
            ] : null,
            'created_at' => $this->resource['created_at'] ?? null,
        ];
    }
}

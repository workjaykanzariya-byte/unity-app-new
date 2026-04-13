<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LifeImpactHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'user_id' => (string) $this->user_id,
            'action_key' => (string) $this->action_key,
            'action_label' => (string) $this->action_label,
            'impact_category' => $this->impact_category,
            'life_impacted' => (int) ($this->life_impacted ?? 0),
            'status' => (string) $this->status,
            'remarks' => $this->remarks,
            'approved_at' => optional($this->approved_at)?->toISOString(),
            'created_at' => optional($this->created_at)?->toISOString(),
            'activity' => [
                'id' => $this->activity_id ? (string) $this->activity_id : null,
                'type' => (string) data_get($this->meta ?? [], 'activity_type', 'activity'),
            ],
        ];
    }
}

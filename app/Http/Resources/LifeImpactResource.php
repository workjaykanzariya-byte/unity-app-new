<?php

namespace App\Http\Resources;

use App\Services\Impacts\LifeImpactActionCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LifeImpactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $action = app(LifeImpactActionCatalog::class)->get((string) $this->action);

        return [
            'id' => (string) $this->id,
            'user_id' => (string) $this->user_id,
            'activity_id' => (string) $this->id,
            'action_key' => (string) $this->action,
            'action_label' => $action['label'] ?? (string) ($this->additional_remarks ?? $this->action),
            'impact_category' => $action['category'] ?? null,
            'life_impacted' => (int) ($this->life_impacted ?? 1),
            'status' => (string) $this->status,
            'remarks' => (string) ($this->story_to_share ?? ''),
            'date' => optional($this->impact_date)?->toDateString(),
            'impacted_peer_id' => $this->impacted_peer_id ? (string) $this->impacted_peer_id : null,
            'approved_at' => optional($this->approved_at)?->toISOString(),
            'created_at' => optional($this->created_at)?->toISOString(),
            'updated_at' => optional($this->updated_at)?->toISOString(),
            'activity' => [
                'id' => (string) $this->id,
                'type' => 'impact',
            ],
        ];
    }
}

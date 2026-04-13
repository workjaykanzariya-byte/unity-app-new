<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LifeImpactHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $activityType = (string) data_get($this->meta ?? [], 'activity_type', '');

        if ($activityType === '' && $this->activity_id) {
            $activityType = (string) ($this->action_key === 'closed_business_deal' ? 'business_deal' : 'activity');
        }

        return [
            'id' => (string) $this->id,
            'user_id' => (string) $this->user_id,
            'user_name' => $this->resolveUserName(),
            'action_key' => (string) $this->action_key,
            'action_label' => (string) $this->action_label,
            'impact_category' => $this->impact_category,
            'life_impacted' => (int) ($this->life_impacted ?? 0),
            'status' => (string) $this->status,
            'remarks' => $this->remarks,
            'approved_at' => optional($this->approved_at)?->toISOString(),
            'counted_in_total' => (bool) $this->counted_in_total,
            'created_at' => optional($this->created_at)?->toISOString(),
            'updated_at' => optional($this->updated_at)?->toISOString(),
            'activity' => [
                'id' => $this->activity_id ? (string) $this->activity_id : null,
                'type' => $activityType !== '' ? $activityType : 'activity',
            ],
        ];
    }

    private function resolveUserName(): ?string
    {
        if (! $this->relationLoaded('user') || ! $this->user) {
            return null;
        }

        $name = trim((string) ($this->user->display_name ?? ''));

        if ($name !== '') {
            return $name;
        }

        $fullName = trim((string) ($this->user->first_name ?? '') . ' ' . (string) ($this->user->last_name ?? ''));

        return $fullName !== '' ? $fullName : null;
    }
}

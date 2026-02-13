<?php

namespace App\Http\Resources\V1;

use App\Models\Circle;
use App\Support\CircleRank;
use Illuminate\Http\Resources\Json\JsonResource;

class CircleResource extends JsonResource
{
    public function toArray($request): array
    {
        $rank = CircleRank::compute((int) $this->active_members_count);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'purpose' => $this->purpose,
            'announcement' => $this->announcement,
            'type' => $this->type,
            'country' => $this->country,
            'city_id' => $this->city_id,
            'industry_tags' => is_array($this->industry_tags) ? $this->industry_tags : [],
            'meeting_mode' => $this->meeting_mode,
            'meeting_frequency' => $this->meeting_frequency,
            'meeting_repeat' => $this->meeting_repeat,
            'launch_date' => optional($this->launch_date)?->toDateString(),
            'annual_fee' => $this->annual_fee,
            'founder_user' => $this->mapUser($this->whenLoaded('founderUser')),
            'director_user' => $this->mapUser($this->whenLoaded('directorUser')),
            'industry_director_user' => $this->mapUser($this->whenLoaded('industryDirectorUser')),
            'ded_user' => $this->mapUser($this->whenLoaded('dedUser')),
            'stage' => $this->stage,
            'stage_label' => Circle::STAGE_LABELS[$this->stage] ?? null,
            'active_members_count' => (int) ($this->active_members_count ?? 0),
            'rank_key' => $rank['rank_key'],
            'rank_label' => $rank['rank_label'],
            'circle_title' => $rank['circle_title'],
            'rank_display' => $rank['rank_label'] . ' – ' . $rank['circle_title'],
            'circle_image_url' => $this->image_file_id ? url('/api/v1/files/' . $this->image_file_id) : null,
        ];
    }

    private function mapUser($user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'display_name' => $user->display_name,
        ];
    }
}

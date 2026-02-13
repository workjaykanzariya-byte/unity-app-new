<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CircleMemberResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'circle_id' => $this->circle_id,
            'role' => $this->role,
            'status' => $this->status,
            'joined_at' => $this->joined_at,
            'left_at' => $this->left_at,
            'substitute_count' => $this->substitute_count,
            'role_id' => $this->role_id,

            'user' => $this->whenLoaded('user', function () {
                $name = $this->user->name
                    ?? $this->user->display_name
                    ?? trim(($this->user->first_name ?? '') . ' ' . ($this->user->last_name ?? ''))
                    ?: $this->user->email;

                return [
                    'id' => $this->user->id,
                    'name' => $name,
                    'email' => $this->user->email,
                    'phone' => $this->user->phone ?? null,
                    'country_code' => $this->user->country_code ?? null,
                    'city_id' => $this->user->city_id ?? null,
                    'city_name' => optional($this->user->cityRelation)->name ?? null,
                    'city' => $this->user->cityRelation ? [
                        'id' => $this->user->cityRelation->id,
                        'name' => $this->user->cityRelation->name,
                        'state' => $this->user->cityRelation->state,
                        'district' => $this->user->cityRelation->district,
                        'country' => $this->user->cityRelation->country,
                        'country_code' => $this->user->cityRelation->country_code,
                    ] : null,
                    'membership_status' => $this->user->membership_status ?? null,
                    'is_active' => $this->user->is_active ?? null,
                    'profile_photo_file_id' => $this->user->profile_photo_file_id ?? null,
                    'profile_photo_url' => ! empty($this->user->profile_photo_file_id)
                        ? url("/api/v1/files/{$this->user->profile_photo_file_id}")
                        : null,
                    'designation' => $this->user->designation ?? null,
                    'company_name' => $this->user->company_name ?? null,
                    'created_at' => optional($this->user->created_at)->toISOString(),
                ];
            }),

            'role_details' => $this->whenLoaded('roleModel', function () {
                return [
                    'id' => $this->roleModel->id,
                    'name' => $this->roleModel->name ?? null,
                    'slug' => $this->roleModel->slug ?? null,
                ];
            }),
        ];
    }
}

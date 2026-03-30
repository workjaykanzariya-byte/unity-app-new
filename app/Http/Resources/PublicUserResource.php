<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $name = $this->display_name
            ?? trim((string) ($this->first_name ?? '') . ' ' . (string) ($this->last_name ?? ''));

        $profileImageUrl = $this->profile_photo_url;

        if (! $profileImageUrl && ! empty($this->profile_photo_file_id)) {
            $profileImageUrl = url('/api/v1/files/' . $this->profile_photo_file_id);
        }

        $circles = $this->whenLoaded('circleMembers', function () {
            return $this->circleMembers
                ->map(fn ($member) => [
                    'id' => optional($member->circle)->id,
                    'name' => optional($member->circle)->name,
                ])
                ->filter(fn ($circle) => ! empty($circle['id']))
                ->values()
                ->all();
        }, []);

        $circleNames = collect($circles)->pluck('name')->filter()->values()->all();
        $statusValue = $this->status ?? 'active';
        $statusLabel = strtolower((string) $statusValue) === 'active' ? 'Active' : ucfirst((string) $statusValue);
        $membershipStatus = $this->membership_status;

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'name' => $name !== '' ? $name : null,
            'email' => $this->email,
            'phone' => $this->phone,
            'profile_slug' => $this->public_profile_slug,
            'company_name' => $this->company_name,
            'designation' => $this->designation,
            'city_id' => $this->city_id,
            'city_name' => optional($this->whenLoaded('city'))->name,
            'country_name' => $this->country,
            'circles' => $circles,
            'circle_names' => $circleNames,
            'circle_name' => $circleNames[0] ?? null,
            'membership_label' => $membershipStatus ? ucfirst(str_replace('_', ' ', (string) $membershipStatus)) : null,
            'profile_image_url' => $profileImageUrl,
            'membership_status' => $membershipStatus,
            'coins' => (int) ($this->coins_balance ?? 0),
            'last_login_at' => $this->last_login_at,
            'status' => $statusLabel,
            'created_at' => $this->created_at,
        ];
    }
}

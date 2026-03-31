<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class PublicMemberProfileResource extends JsonResource
{
    public function toArray($request): array
    {
        $profilePhotoId = $this->profile_photo_file_id;
        $coverPhotoId = $this->cover_photo_file_id;
        $membershipStatus = $this->membership_status;
        $cityRelation = $this->resource->relationLoaded('city') ? $this->resource->getRelation('city') : null;

        return [
            'id' => $this->id,
            'public_profile_slug' => $this->public_profile_slug,
            'profile_photo_id' => $profilePhotoId,
            'cover_photo_id' => $coverPhotoId,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'display_name' => $this->display_name,
            'company_name' => $this->company_name,
            'designation' => $this->designation,
            'email' => $this->email,
            'phone' => $this->phone,
            'city' => $cityRelation?->name ?? $this->resource->getAttribute('city'),
            'membership_status' => $membershipStatus,
            'membership_expiry' => $this->membership_expiry,
            'membership_status_label' => $this->membershipStatusLabel($membershipStatus),
            'membership_starts_at' => $this->membership_starts_at,
            'membership_ends_at' => $this->membership_ends_at,
            'zoho_plan_code' => $this->zoho_plan_code,
            'zoho_last_invoice_id' => $this->zoho_last_invoice_id,
            'active_circle_id' => $this->active_circle_id,
            'active_circle_addon_code' => $this->active_circle_addon_code,
            'active_circle_addon_name' => $this->active_circle_addon_name,
            'circle_joined_at' => $this->circle_joined_at,
            'circle_expires_at' => $this->circle_expires_at,
            'active_circle_subscription_id' => $this->active_circle_subscription_id,
            'active_circle' => $this->activeCircle
                ? [
                    'id' => $this->activeCircle->id,
                    'name' => $this->activeCircle->name,
                ]
                : null,
            'circle_memberships' => $this->resolveCircleMemberships(),
            'coins_balance' => $this->coins_balance,
            'business_type' => $this->business_type,
            'turnover_range' => $this->turnover_range,
            'gender' => $this->gender,
            'dob' => optional($this->dob)?->format('Y-m-d'),
            'experience_years' => $this->experience_years,
            'experience_summary' => $this->experience_summary,
            'bio' => $this->short_bio,
            'long_bio_html' => $this->long_bio_html,
            'industry_tags' => $this->industry_tags ?? [],
            'skills' => $this->skills ?? [],
            'interests' => $this->interests ?? [],
            'target_regions' => $this->target_regions ?? [],
            'target_business_categories' => $this->target_business_categories ?? [],
            'hobbies_interests' => $this->hobbies_interests ?? [],
            'leadership_roles' => $this->leadership_roles ?? [],
            'special_recognitions' => $this->special_recognitions ?? [],
            'social_links' => $this->social_links,
            'profile_photo_url' => $profilePhotoId ? url('/api/v1/files/' . $profilePhotoId) : null,
            'cover_photo_url' => $coverPhotoId ? url('/api/v1/files/' . $coverPhotoId) : null,
            'address' => null,
            'state' => null,
            'country' => null,
            'pincode' => null,
            'is_verified' => null,
            'is_sponsored_member' => (bool) $this->is_sponsored_member,
            'last_login_at' => $this->last_login_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'medal_rank' => $this->coin_medal_rank,
            'title' => $this->coin_milestone_title,
            'meaning_and_vibe' => $this->coin_milestone_meaning,
            'contribution_award_name' => $this->contribution_award_name,
            'contribution_recognition' => $this->contribution_award_recognition,
        ];
    }

    private function membershipStatusLabel(?string $status): ?string
    {
        if ($status === null || trim($status) === '') {
            return null;
        }

        return Str::of($status)
            ->replace(['-', '_'], ' ')
            ->title()
            ->toString();
    }

    private function resolveCircleMemberships(): array
    {
        if (! $this->relationLoaded('circleMembers')) {
            return [];
        }

        return $this->circleMembers
            ->filter(fn ($membership) => $membership->deleted_at === null)
            ->map(fn ($membership): array => [
                'circle_member_id' => $membership->id,
                'circle_id' => $membership->circle_id,
                'circle_name' => $membership->circle?->name,
                'role' => $membership->role,
                'status' => $membership->status,
            ])
            ->values()
            ->all();
    }
}

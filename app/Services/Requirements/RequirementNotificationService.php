<?php

namespace App\Services\Requirements;

use App\Models\Requirement;
use App\Models\User;
use App\Services\Notifications\NotifyUserService;

class RequirementNotificationService
{
    public function __construct(private readonly NotifyUserService $notifyUserService)
    {
    }

    public function notifyRequirementCreated(Requirement $requirement): void
    {
        $creator = $requirement->user;
        if (! $creator) {
            return;
        }

        $eligibleUsers = User::query()
            ->where('id', '!=', $creator->id)
            ->whereNull('deleted_at')
            ->get()
            ->filter(fn (User $user) => $this->matchesFilters($user, $requirement));

        foreach ($eligibleUsers as $user) {
            $this->notifyUserService->notifyUser(
                $user,
                $creator,
                'requirement_created',
                [
                    'requirement_id' => (string) $requirement->id,
                    'requirement_subject' => $requirement->subject,
                    'from_user_id' => (string) $creator->id,
                    'from_user_name' => $creator->display_name,
                    'from_company' => $creator->company_name,
                    'from_city' => $creator->city,
                    'to_user_id' => (string) $user->id,
                    'title' => 'New requirement posted',
                    'body' => $creator->display_name . ' posted a new requirement: ' . $requirement->subject,
                ],
                $requirement
            );
        }
    }

    public function notifyRequirementInterest(Requirement $requirement, User $interestedUser, ?string $comment = null): void
    {
        $creator = $requirement->user;
        if (! $creator) {
            return;
        }

        $this->notifyUserService->notifyUser(
            $creator,
            $interestedUser,
            'requirement_interest',
            [
                'requirement_id' => (string) $requirement->id,
                'requirement_subject' => $requirement->subject,
                'from_user_id' => (string) $interestedUser->id,
                'from_user_name' => $interestedUser->display_name,
                'from_company' => $interestedUser->company_name,
                'from_city' => $interestedUser->city,
                'from_profile_photo_url' => $interestedUser->profile_photo_url,
                'comment' => $comment,
                'to_user_id' => (string) $creator->id,
                'title' => 'New interest on your requirement',
                'body' => $interestedUser->display_name . ' expressed interest in: ' . $requirement->subject,
            ],
            $requirement
        );
    }

    private function matchesFilters(User $user, Requirement $requirement): bool
    {
        $regions = collect($requirement->region_filter ?? [])->filter()->values();
        $categories = collect($requirement->category_filter ?? [])->filter()->values();

        if ($regions->isEmpty() && $categories->isEmpty()) {
            return true;
        }

        $regionMatched = true;
        if ($regions->isNotEmpty()) {
            if ($regions->contains(fn ($value) => strcasecmp((string) $value, 'National') === 0)) {
                $regionMatched = true;
            } elseif (empty($user->city) && empty($user->target_regions)) {
                $regionMatched = true;
            } else {
                $regionMatched = $regions->contains(fn ($value) => strcasecmp((string) $value, (string) $user->city) === 0)
                    || collect($user->target_regions ?? [])->contains(fn ($value) => $regions->contains(fn ($region) => strcasecmp((string) $region, (string) $value) === 0));
            }
        }

        $categoryMatched = true;
        if ($categories->isNotEmpty()) {
            if (empty($user->business_type) && empty($user->target_business_categories) && empty($user->industry_tags)) {
                $categoryMatched = true;
            } else {
                $categoryMatched = $categories->contains(fn ($value) => strcasecmp((string) $value, (string) $user->business_type) === 0)
                    || collect($user->target_business_categories ?? [])->contains(fn ($value) => $categories->contains(fn ($category) => strcasecmp((string) $category, (string) $value) === 0))
                    || collect($user->industry_tags ?? [])->contains(fn ($value) => $categories->contains(fn ($category) => strcasecmp((string) $category, (string) $value) === 0));
            }
        }

        return $regionMatched && $categoryMatched;
    }
}

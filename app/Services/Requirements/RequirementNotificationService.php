<?php

namespace App\Services\Requirements;

use App\Models\Requirement;
use App\Models\User;
use App\Services\Notifications\NotifyUserService;
use Illuminate\Support\Facades\Log;
use Throwable;

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
            ->filter(fn (User $user): bool => $this->matchesFilters($user, $requirement));

        foreach ($eligibleUsers as $user) {
            try {
                $this->notifyUserService->notifyUser(
                    $user,
                    $creator,
                    'requirement_created',
                    [
                        'notification_type' => 'requirement_created',
                        'requirement_id' => (string) $requirement->id,
                        'requirement_subject' => $requirement->subject,
                        'from_user_id' => (string) $creator->id,
                        'from_user_name' => $this->resolveUserName($creator),
                        'from_company' => $this->resolveUserCompany($creator),
                        'from_city' => $creator->city,
                        'to_user_id' => (string) $user->id,
                        'title' => 'New requirement posted',
                        'body' => $this->resolveUserName($creator) . ' posted: ' . $requirement->subject,
                    ],
                    $requirement
                );
            } catch (Throwable $exception) {
                Log::warning('Requirement created notification failed.', [
                    'requirement_id' => (string) $requirement->id,
                    'to_user_id' => (string) $user->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    public function notifyRequirementInterest(Requirement $requirement, User $interestedUser, ?string $comment): void
    {
        $creator = $requirement->user;

        if (! $creator || (string) $creator->id === (string) $interestedUser->id) {
            return;
        }

        try {
            $this->notifyUserService->notifyUser(
                $creator,
                $interestedUser,
                'requirement_interest',
                [
                    'notification_type' => 'requirement_interest',
                    'requirement_id' => (string) $requirement->id,
                    'requirement_subject' => $requirement->subject,
                    'from_user_id' => (string) $interestedUser->id,
                    'from_user_name' => $this->resolveUserName($interestedUser),
                    'from_company' => $this->resolveUserCompany($interestedUser),
                    'from_city' => $interestedUser->city,
                    'from_profile_photo_url' => $interestedUser->profile_photo_url,
                    'comment' => $comment,
                    'to_user_id' => (string) $creator->id,
                    'title' => 'New requirement interest',
                    'body' => $this->resolveUserName($interestedUser) . ' expressed interest in: ' . $requirement->subject,
                ],
                $requirement
            );
        } catch (Throwable $exception) {
            Log::warning('Requirement interest notification failed.', [
                'requirement_id' => (string) $requirement->id,
                'to_user_id' => (string) $creator->id,
                'from_user_id' => (string) $interestedUser->id,
                'error' => $exception->getMessage(),
            ]);
        }
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
            if ($regions->contains(fn ($region): bool => strcasecmp((string) $region, 'National') === 0)) {
                $regionMatched = true;
            } elseif (! empty($user->city)) {
                $regionMatched = $regions->contains(fn ($region): bool => strcasecmp((string) $region, (string) $user->city) === 0);
            }
        }

        $categoryMatched = true;
        if ($categories->isNotEmpty()) {
            $userCategories = collect([])
                ->merge((array) ($user->target_business_categories ?? []))
                ->merge((array) ($user->industry_tags ?? []))
                ->push((string) ($user->business_type ?? ''))
                ->filter();

            if ($userCategories->isNotEmpty()) {
                $categoryMatched = $userCategories->contains(function ($value) use ($categories): bool {
                    return $categories->contains(fn ($category): bool => strcasecmp((string) $category, (string) $value) === 0);
                });
            }
        }

        return $regionMatched && $categoryMatched;
    }

    private function resolveUserName(User $user): string
    {
        return (string) ($user->name
            ?? $user->full_name
            ?? $user->display_name
            ?? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))
            ?? 'Peer');
    }

    private function resolveUserCompany(User $user): string
    {
        return (string) ($user->company ?? $user->company_name ?? '');
    }
}

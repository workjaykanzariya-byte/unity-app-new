<?php

namespace App\Services\Coins;

use App\Models\Activity;
use App\Models\BusinessDeal;
use App\Models\P2pMeeting;
use App\Models\Referral;
use App\Models\Requirement;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CoinsLedgerActivityResolver
{
    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\CoinLedger>  $ledgerItems
     * @return array{
     *     activities: \Illuminate\Support\Collection<string, \App\Models\Activity>,
     *     activityTitles: array<string, string>,
     *     activitySummaries: array<string, array|null>,
     *     activityRelatedUserIds: array<string, string|null>,
     *     relatedUsers: \Illuminate\Support\Collection<string, \App\Models\User>
     * }
     */
    public function resolve(Collection $ledgerItems, int|string $authUserId, CoinActivityTitleResolver $titleResolver): array
    {
        $activityMap = $this->loadActivities($ledgerItems);

        if ($activityMap->isEmpty()) {
            return [
                'activities' => collect(),
                'activityTitles' => [],
                'activitySummaries' => [],
                'activityRelatedUserIds' => [],
                'relatedUsers' => collect(),
            ];
        }

        $typedActivities = $this->loadTypedActivities($activityMap);
        $activityTitles = $titleResolver->resolveTitles($activityMap->values());
        $activityRelatedUserIds = [];
        $activitySummaries = [];

        foreach ($activityMap as $activity) {
            $normalizedType = $this->normalizeActivityType($activity->type);
            $typedActivity = $typedActivities[$normalizedType][$activity->id] ?? null;

            $activityRelatedUserIds[$activity->id] = $this->resolveRelatedUserId(
                $normalizedType,
                $typedActivity,
                $activity,
                $authUserId
            );

            $activitySummaries[$activity->id] = $this->buildActivitySummary(
                $activity,
                $activityTitles[$activity->id] ?? $titleResolver->defaultTitle($activity->type),
                $this->resolveExtraLabel($normalizedType, $typedActivity)
            );
        }

        $relatedUsers = $this->loadRelatedUsers($ledgerItems, $activityMap, $activityRelatedUserIds);

        return [
            'activities' => $activityMap,
            'activityTitles' => $activityTitles,
            'activitySummaries' => $activitySummaries,
            'activityRelatedUserIds' => $activityRelatedUserIds,
            'relatedUsers' => $relatedUsers,
        ];
    }

    private function loadActivities(Collection $ledgerItems): Collection
    {
        $activityIds = $ledgerItems->pluck('activity_id')
            ->filter()
            ->unique()
            ->values();

        if ($activityIds->isEmpty()) {
            return collect();
        }

        return Activity::whereIn('id', $activityIds)->get()->keyBy('id');
    }

    /**
     * @param  \Illuminate\Support\Collection<string, \App\Models\Activity>  $activities
     * @return array<string, \Illuminate\Support\Collection<string, \Illuminate\Database\Eloquent\Model>>
     */
    private function loadTypedActivities(Collection $activities): array
    {
        $grouped = $activities->groupBy(fn ($activity) => $this->normalizeActivityType($activity->type));
        $typed = [];

        foreach ($grouped as $type => $activityGroup) {
            if (! $type) {
                continue;
            }

            $ids = $activityGroup->pluck('id')->all();

            switch ($type) {
                case 'p2p_meeting':
                    $typed[$type] = P2pMeeting::whereIn('id', $ids)
                        ->get(['id', 'initiator_user_id', 'peer_user_id', 'remarks', 'meeting_place'])
                        ->keyBy('id');

                    break;

                case 'referral':
                    $typed[$type] = Referral::whereIn('id', $ids)
                        ->get(['id', 'from_user_id', 'to_user_id', 'referral_of', 'referral_type', 'remarks'])
                        ->keyBy('id');

                    break;

                case 'testimonial':
                    $typed[$type] = Testimonial::whereIn('id', $ids)
                        ->get(['id', 'from_user_id', 'to_user_id', 'content'])
                        ->keyBy('id');

                    break;

                case 'business_deal':
                    $typed[$type] = BusinessDeal::whereIn('id', $ids)
                        ->get(['id', 'from_user_id', 'to_user_id', 'partner_user_id', 'business_type', 'comment', 'deal_amount'])
                        ->keyBy('id');

                    break;

                case 'requirement':
                    $typed[$type] = Requirement::whereIn('id', $ids)
                        ->get()
                        ->keyBy('id');

                    break;

                default:
                    $typed[$type] = collect();
                    break;
            }
        }

        return $typed;
    }

    private function normalizeActivityType(?string $type): ?string
    {
        if (! $type) {
            return null;
        }

        return Str::of($type)
            ->afterLast('\\')
            ->snake()
            ->toString();
    }

    private function resolveRelatedUserId(?string $type, ?Model $typedActivity, ?Activity $activity, int|string $authUserId): ?string
    {
        if ($typedActivity === null || $type === null) {
            return $activity?->related_user_id;
        }

        return match ($type) {
            'p2p_meeting' => $this->selectCounterparty([
                $typedActivity->initiator_user_id ?? null,
                $typedActivity->peer_user_id ?? null,
            ], $authUserId) ?? $activity?->related_user_id,
            'referral' => $this->selectCounterparty([
                $typedActivity->from_user_id ?? null,
                $typedActivity->to_user_id ?? null,
            ], $authUserId) ?? $activity?->related_user_id,
            'testimonial' => $this->selectCounterparty([
                $typedActivity->from_user_id ?? null,
                $typedActivity->to_user_id ?? null,
            ], $authUserId) ?? $activity?->related_user_id,
            'business_deal' => $this->selectCounterparty([
                $typedActivity->from_user_id ?? null,
                $typedActivity->to_user_id ?? null,
                $typedActivity->partner_user_id ?? null,
            ], $authUserId) ?? $activity?->related_user_id,
            'requirement' => $this->resolveRequirementRelatedUserId($typedActivity, $authUserId) ?? $activity?->related_user_id,
            default => $activity?->related_user_id,
        };
    }

    private function resolveRequirementRelatedUserId(Model $requirement, int|string $authUserId): ?string
    {
        $candidateKeys = [
            'fulfilled_by_user_id',
            'assigned_to_user_id',
            'related_user_id',
            'to_user_id',
            'for_user_id',
            'receiver_user_id',
            'partner_user_id',
            'accepted_by_user_id',
        ];

        foreach ($candidateKeys as $key) {
            $value = $this->getAttributeIfExists($requirement, $key);

            if ($value && $value !== $authUserId) {
                return $value;
            }
        }

        return null;
    }

    private function getAttributeIfExists(Model $model, string $key): mixed
    {
        $attributes = $model->getAttributes();

        if (array_key_exists($key, $attributes)) {
            return $attributes[$key];
        }

        return null;
    }

    private function resolveExtraLabel(?string $type, ?Model $typedActivity): ?string
    {
        if (! $typedActivity || ! $type) {
            return null;
        }

        return match ($type) {
            'p2p_meeting' => $this->normalizeLabel($typedActivity->meeting_place ?? $typedActivity->remarks ?? null),
            'referral' => $this->normalizeLabel($typedActivity->referral_type ?? $typedActivity->referral_of ?? null),
            'testimonial' => null,
            'requirement' => $this->normalizeLabel($this->getAttributeIfExists($typedActivity, 'status')),
            'business_deal' => $this->normalizeLabel($typedActivity->business_type ?? $typedActivity->comment ?? null),
            default => null,
        };
    }

    private function normalizeLabel(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : Str::limit($trimmed, 120);
    }

    private function selectCounterparty(array $candidateIds, int|string $authUserId): ?string
    {
        foreach ($candidateIds as $id) {
            if ($id && $id !== $authUserId) {
                return $id;
            }
        }

        return null;
    }

    private function buildActivitySummary(Activity $activity, string $title, ?string $extraLabel): array
    {
        return [
            'type' => $activity->type ?? null,
            'id' => $activity->id,
            'title' => $title,
            'extra_label' => $extraLabel,
        ];
    }

    private function loadRelatedUsers(Collection $ledgerItems, Collection $activities, array $computedUserIds): Collection
    {
        $ledgerRelatedIds = $ledgerItems->pluck('related_user_id')->filter();
        $activityRelatedIds = $activities->pluck('related_user_id')->filter();

        $allUserIds = collect($computedUserIds)
            ->filter()
            ->concat($ledgerRelatedIds)
            ->concat($activityRelatedIds)
            ->unique()
            ->values();

        if ($allUserIds->isEmpty()) {
            return collect();
        }

        return User::whereIn('id', $allUserIds)->get()->keyBy('id');
    }
}

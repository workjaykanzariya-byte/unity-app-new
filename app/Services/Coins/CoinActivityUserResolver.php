<?php

namespace App\Services\Coins;

use App\Models\Activity;
use App\Models\BusinessDeal;
use App\Models\P2pMeeting;
use App\Models\Referral;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Support\Collection;

class CoinActivityUserResolver
{
    /**
     * Resolve counterparties for ledger items and return loaded users.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\CoinLedger>  $ledgerItems
     * @param  \Illuminate\Support\Collection<string, Activity>  $activities
     * @return array{ledgerUserIds: array<string, string|null>, users: \Illuminate\Support\Collection<string, User>}
     */
    public function resolve(Collection $ledgerItems, Collection $activities, int|string $authUserId): array
    {
        $activityTypeBuckets = [];

        foreach ($ledgerItems as $ledger) {
            $activity = $ledger->activity_id ? $activities->get($ledger->activity_id) : null;
            $type = $activity?->type;

            if (! $activity || ! $type) {
                continue;
            }

            $activityTypeBuckets[$type][] = $activity->id;
        }

        $typedRecords = $this->loadTypedRecords($activityTypeBuckets);

        $ledgerUserIds = [];
        $userIds = collect();

        foreach ($ledgerItems as $ledger) {
            $activity = $ledger->activity_id ? $activities->get($ledger->activity_id) : null;
            $type = $activity?->type;

            if (! $activity || ! $type) {
                continue;
            }

            $ledgerKey = (string) ($ledger->transaction_id ?? $ledger->id ?? spl_object_id($ledger));
            $otherUserId = $this->resolveUserIdForType($type, $activity->id, $typedRecords, $authUserId);

            $ledgerUserIds[$ledgerKey] = $otherUserId;

            if ($otherUserId) {
                $userIds->push($otherUserId);
            }

            if ($ledger->related_user_id) {
                $userIds->push($ledger->related_user_id);
            }

            if ($activity->related_user_id) {
                $userIds->push($activity->related_user_id);
            }
        }

        $users = $userIds->filter()->unique()->values();

        $userMap = $users->isEmpty()
            ? collect()
            : User::whereIn('id', $users)->get()->keyBy('id');

        return [
            'ledgerUserIds' => $ledgerUserIds,
            'users' => $userMap,
        ];
    }

    /**
     * @param  array<string, array<int, string>>  $activityTypeBuckets
     * @return array<string, \Illuminate\Support\Collection<string, mixed>>
     */
    private function loadTypedRecords(array $activityTypeBuckets): array
    {
        $typed = [];

        foreach ($activityTypeBuckets as $type => $ids) {
            switch ($type) {
                case 'p2p_meeting':
                    $typed[$type] = P2pMeeting::whereIn('id', $ids)
                        ->get(['id', 'initiator_user_id', 'peer_user_id'])
                        ->keyBy('id');

                    break;

                case 'referral':
                    $typed[$type] = Referral::whereIn('id', $ids)
                        ->get(['id', 'from_user_id', 'to_user_id'])
                        ->keyBy('id');

                    break;

                case 'testimonial':
                    $typed[$type] = Testimonial::whereIn('id', $ids)
                        ->get(['id', 'from_user_id', 'to_user_id'])
                        ->keyBy('id');

                    break;

                case 'business_deal':
                    $typed[$type] = BusinessDeal::whereIn('id', $ids)
                        ->get(['id', 'from_user_id', 'to_user_id'])
                        ->keyBy('id');

                    break;

                default:
                    $typed[$type] = collect();
                    break;
            }
        }

        return $typed;
    }

    private function resolveUserIdForType(string $type, string $activityId, array $typedRecords, int|string $authUserId): ?string
    {
        $record = $typedRecords[$type][$activityId] ?? null;

        if (! $record) {
            return null;
        }

        return match ($type) {
            'p2p_meeting' => $record->initiator_user_id === $authUserId
                ? ($record->peer_user_id ?? null)
                : ($record->initiator_user_id ?? null),
            'referral', 'testimonial', 'business_deal' => $record->from_user_id === $authUserId
                ? ($record->to_user_id ?? null)
                : ($record->from_user_id ?? null),
            default => null,
        };
    }
}

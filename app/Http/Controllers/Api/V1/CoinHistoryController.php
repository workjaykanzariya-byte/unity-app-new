<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\CoinHistoryItemResource;
use App\Models\CoinLedger;
use App\Services\Coins\CoinActivityTitleResolver;
use App\Services\Coins\CoinActivityUserResolver;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CoinHistoryController extends BaseApiController
{
    public function index(Request $request, CoinActivityTitleResolver $titleResolver, CoinActivityUserResolver $userResolver)
    {
        $validator = validator($request->all(), [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'type' => ['sometimes', 'string', Rule::in([
                'testimonial',
                'referral',
                'requirement',
                'business_deal',
                'p2p_meeting',
            ])],
            'from_date' => ['sometimes', 'date_format:Y-m-d'],
            'to_date' => ['sometimes', 'date_format:Y-m-d'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:5000'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors());
        }

        $data = $validator->validated();
        $user = $request->user();

        $perPage = (int) ($data['per_page'] ?? 20);
        $limit = (int) ($data['limit'] ?? 2000);

        $query = CoinLedger::query()
            ->where('user_id', $user->id);

        if (! empty($data['type'])) {
            $query->whereHas('activity', function ($activityQuery) use ($data) {
                $activityQuery->where('type', $data['type']);
            });
        }

        if (! empty($data['from_date'])) {
            $fromDate = CarbonImmutable::createFromFormat('Y-m-d', $data['from_date'])->startOfDay();
            $query->where('created_at', '>=', $fromDate);
        }

        if (! empty($data['to_date'])) {
            $toDate = CarbonImmutable::createFromFormat('Y-m-d', $data['to_date'])->endOfDay();
            $query->where('created_at', '<=', $toDate);
        }

        $ledgerItems = $query
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $activities = $this->loadActivities($ledgerItems);
        $activityTitles = $titleResolver->resolveTitles($activities->values());
        $relatedUsers = $userResolver->resolve($ledgerItems, $activities, $user->id);

        $transformed = $ledgerItems->map(function ($ledger) use ($activities, $activityTitles, $relatedUsers, $titleResolver) {
            $activity = $ledger->activity_id ? $activities->get($ledger->activity_id) : null;
            $activityType = $activity->type ?? null;

            $ledgerKey = (string) ($ledger->transaction_id ?? $ledger->id ?? null);
            $relatedUserId = $relatedUsers['ledgerUserIds'][$ledgerKey] ?? null;
            $relatedUserId = $relatedUserId
                ?? ($ledger->related_user_id ?? null)
                ?? ($activity->related_user_id ?? null);
            $relatedUser = $relatedUserId ? $relatedUsers['users']->get($relatedUserId) : null;

            $reasonKey = $ledger->reason_key ?? $ledger->reference ?? ($activityType ? 'activity_'.$activityType : 'coins_transaction');
            $activityTitle = $activity ? ($activityTitles[$activity->id] ?? $titleResolver->defaultTitle($activityType)) : $titleResolver->defaultTitle($activityType);

            return [
                'id' => $ledger->transaction_id ?? $ledger->id ?? null,
                'coins_delta' => (int) ($ledger->coins_delta ?? $ledger->amount ?? 0),
                'reason_label' => $this->formatReasonLabel($reasonKey),
                'activity_type' => $activityType,
                'activity_id' => $ledger->activity_id,
                'activity_title' => $activityTitle,
                'related_user' => $relatedUser,
                'created_at' => $ledger->created_at,
            ];
        });

        return $this->success([
            'current_coins_balance' => (int) $user->coins_balance,
            'items' => CoinHistoryItemResource::collection($transformed),
        ], 'Coins history fetched successfully');
    }

    private function loadActivities($ledgerItems)
    {
        $activityIds = $ledgerItems->pluck('activity_id')
            ->filter()
            ->unique()
            ->values();

        if ($activityIds->isEmpty()) {
            return collect();
        }

        return \App\Models\Activity::whereIn('id', $activityIds)->get()->keyBy('id');
    }

    private function formatReasonLabel(?string $reasonKey): string
    {
        if (! $reasonKey) {
            return 'Coins Update';
        }

        $normalized = Str::of($reasonKey)
            ->replace(['_', '-'], ' ')
            ->trim();

        if ($normalized->isEmpty()) {
            return 'Coins Update';
        }

        return $normalized->headline()->toString();
    }
}

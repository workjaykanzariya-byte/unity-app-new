<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\CoinHistoryItemResource;
use App\Models\Activity;
use App\Models\CoinLedger;
use App\Models\User;
use App\Services\Coins\CoinActivityTitleResolver;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CoinHistoryController extends BaseApiController
{
    public function index(Request $request, CoinActivityTitleResolver $titleResolver)
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
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors());
        }

        $data = $validator->validated();
        $user = $request->user();

        $perPage = (int) ($data['per_page'] ?? 20);

        $query = CoinLedger::query()
            ->where('user_id', $user->id);

        if (! empty($data['type'])) {
            $query->where(function ($q) use ($data) {
                $q->whereHas('activity', function ($activityQuery) use ($data) {
                    $activityQuery->where('type', $data['type']);
                })->orWhere(function ($ledgerQuery) use ($data) {
                    $ledgerQuery->whereNull('activity_id')
                        ->where(function ($refQuery) use ($data) {
                            $refQuery->where('reference', $data['type'])
                                ->orWhere('reference', 'ilike', $data['type'].'%');
                        });
                });
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

        $paginator = $query
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $items = collect($paginator->items());

        $activityMap = $this->loadActivities($items);
        $activityTitles = $titleResolver->resolveTitles($activityMap->values());
        $relatedUsers = $this->loadRelatedUsers($activityMap);

        $transformed = $items->map(function ($ledger) use ($activityMap, $activityTitles, $relatedUsers, $titleResolver) {
            $activity = $ledger->activity_id ? $activityMap->get($ledger->activity_id) : null;
            $relatedUser = $activity ? $relatedUsers->get($activity->related_user_id) : null;
            $reasonKey = $ledger->reference ?? ($activity ? 'activity_'.$activity->type : 'coins_transaction');
            $activityType = $activity->type ?? null;
            $activityTitle = $activity ? ($activityTitles[$activity->id] ?? $titleResolver->defaultTitle($activityType)) : $titleResolver->defaultTitle(null);

            return [
                'id' => $ledger->transaction_id,
                'coins_delta' => (int) $ledger->amount,
                'reason_key' => $reasonKey,
                'reason_label' => $this->formatReasonLabel($reasonKey),
                'activity_type' => $activityType,
                'activity_id' => $ledger->activity_id,
                'activity_title' => $activityTitle,
                'related_user' => $relatedUser ? [
                    'id' => $relatedUser->id,
                    'display_name' => $relatedUser->display_name ?? trim($relatedUser->first_name.' '.($relatedUser->last_name ?? '')),
                    'profile_photo_url' => $relatedUser->profile_photo_url,
                ] : null,
                'created_at' => $ledger->created_at,
            ];
        });

        return $this->success([
            'current_coins_balance' => (int) $user->coins_balance,
            'items' => CoinHistoryItemResource::collection($transformed),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ], 'Coins history fetched successfully');
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

    private function loadRelatedUsers(Collection $activities): Collection
    {
        $relatedUserIds = $activities
            ->pluck('related_user_id')
            ->filter()
            ->unique()
            ->values();

        if ($relatedUserIds->isEmpty()) {
            return collect();
        }

        return User::whereIn('id', $relatedUserIds)
            ->get()
            ->keyBy('id');
    }

    private function formatReasonLabel(string $reasonKey): string
    {
        $normalized = Str::of($reasonKey)
            ->replace(['_', '-'], ' ')
            ->trim();

        if ($normalized->isEmpty()) {
            return 'Coins Update';
        }

        return $normalized->headline()->toString();
    }
}

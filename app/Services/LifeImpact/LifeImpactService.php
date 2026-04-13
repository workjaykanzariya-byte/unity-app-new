<?php

namespace App\Services\LifeImpact;

use App\Models\BusinessDeal;
use App\Models\LifeImpactHistory;
use App\Models\User;
use App\Services\Impacts\LifeImpactActionCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class LifeImpactService
{
    public function __construct(private readonly LifeImpactActionCatalog $catalog)
    {
    }

    public function createBusinessDealImpact(User $user, BusinessDeal $deal): array
    {
        return DB::transaction(function () use ($user, $deal): array {
            $action = $this->catalog->get('closed_business_deal');

            if (! $action) {
                throw new \RuntimeException('Life impact action mapping missing for closed_business_deal.');
            }

            Log::info('life_impact.mapping_resolved', [
                'action_key' => $action['key'],
                'life_impacted' => $action['life_impacted'],
                'business_deal_id' => (string) $deal->id,
                'user_id' => (string) $user->id,
            ]);

            $existing = LifeImpactHistory::query()
                ->where('user_id', $user->id)
                ->where('activity_id', $deal->id)
                ->where('action_key', $action['key'])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $totalAfter = $this->currentTotal($user);

                Log::warning('life_impact.duplicate_prevented', [
                    'history_id' => (string) $existing->id,
                    'business_deal_id' => (string) $deal->id,
                    'user_id' => (string) $user->id,
                ]);

                return [
                    'history' => $existing,
                    'earned' => (int) ($existing->life_impacted ?? 0),
                    'total_after' => $totalAfter,
                ];
            }

            $history = LifeImpactHistory::query()->create([
                'user_id' => $user->id,
                'activity_id' => $deal->id,
                'action_key' => $action['key'],
                'action_label' => $action['label'],
                'impact_category' => $action['category'],
                'life_impacted' => $action['life_impacted'],
                'remarks' => (string) ($deal->comment ?? ''),
                'meta' => [
                    'activity_type' => 'business_deal',
                    'deal_date' => $deal->deal_date,
                    'deal_amount' => $deal->deal_amount,
                    'to_user_id' => (string) $deal->to_user_id,
                ],
                'status' => 'approved',
                'approved_at' => now(),
                'counted_in_total' => true,
                'created_by' => $user->id,
            ]);

            Log::info('life_impact.history_created', [
                'history_id' => (string) $history->id,
                'business_deal_id' => (string) $deal->id,
                'user_id' => (string) $user->id,
                'life_impacted' => (int) $history->life_impacted,
            ]);

            $totalAfter = $this->incrementUserTotalLifeImpacted($user, (int) $history->life_impacted);

            return [
                'history' => $history,
                'earned' => (int) $history->life_impacted,
                'total_after' => $totalAfter,
            ];
        });
    }

    public function actions(): array
    {
        return $this->catalog->toList()->all();
    }

    public function historyQueryForUser(User $user): Builder
    {
        return LifeImpactHistory::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at');
    }

    public function summaryForUser(User $user): array
    {
        $query = $this->historyQueryForUser($user);

        $approved = (int) (clone $query)->where('status', 'approved')->sum('life_impacted');
        $pending = (int) (clone $query)->where('status', 'pending')->sum('life_impacted');
        $totalRecords = (int) (clone $query)->count();
        $latest = (clone $query)->first();

        $total = $this->syncUserLifeTotal($user);

        return [
            'user_id' => (string) $user->id,
            'total_life_impacted' => $total,
            'approved_life_impacted' => $approved,
            'pending_life_impacted' => $pending,
            'total_records' => $totalRecords,
            'latest_activity' => $latest ? [
                'id' => (string) $latest->id,
                'action_key' => (string) $latest->action_key,
                'action_label' => (string) $latest->action_label,
                'life_impacted' => (int) ($latest->life_impacted ?? 0),
                'created_at' => optional($latest->created_at)->toISOString(),
            ] : null,
        ];
    }

    public function syncUserLifeTotal(User $user): int
    {
        $sum = (int) LifeImpactHistory::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('counted_in_total', true)
            ->sum('life_impacted');

        $updates = [];

        if (Schema::hasColumn('users', 'total_life_impacted')) {
            $updates['total_life_impacted'] = $sum;
        }

        if (Schema::hasColumn('users', 'life_impacted_count')) {
            $updates['life_impacted_count'] = $sum;
        }

        if (! empty($updates)) {
            User::query()->where('id', $user->id)->update($updates);
        }

        return $sum;
    }

    private function incrementUserTotalLifeImpacted(User $user, int $increment): int
    {
        $locked = User::query()->where('id', $user->id)->lockForUpdate()->firstOrFail();

        $base = $this->currentTotal($locked);
        $newTotal = $base + max(0, $increment);

        $updates = [];

        if (Schema::hasColumn('users', 'total_life_impacted')) {
            $updates['total_life_impacted'] = $newTotal;
        }

        if (Schema::hasColumn('users', 'life_impacted_count')) {
            $updates['life_impacted_count'] = $newTotal;
        }

        if (! empty($updates)) {
            User::query()->where('id', $locked->id)->update($updates);
        }

        Log::info('life_impact.total_incremented', [
            'user_id' => (string) $locked->id,
            'increment_by' => $increment,
            'total_after' => $newTotal,
        ]);

        return $newTotal;
    }

    private function currentTotal(User $user): int
    {
        if (Schema::hasColumn('users', 'total_life_impacted')) {
            return (int) ($user->total_life_impacted ?? 0);
        }

        return (int) ($user->life_impacted_count ?? 0);
    }
}

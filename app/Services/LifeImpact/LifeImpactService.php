<?php

namespace App\Services\LifeImpact;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LifeImpactService
{
    public function addLifeImpact(
        string $userId,
        ?string $triggeredByUserId,
        string $activityType,
        ?string $activityId = null,
        int $impactValue = 0,
        string $title = '',
        ?string $description = null,
        array $meta = [],
    ): int {
        $impactValue = (int) $impactValue;
        $activityId = (is_string($activityId) && Str::isUuid($activityId))
            ? $activityId
            : null;

        if ($impactValue <= 0) {
            return $this->getCurrentTotal($userId);
        }

        return (int) DB::transaction(function () use ($userId, $impactValue, $activityType, $title, $triggeredByUserId, $activityId, $description, $meta) {
            DB::table('users')
                ->where('id', $userId)
                ->update([
                    'life_impacted_count' => DB::raw('COALESCE(life_impacted_count, 0) + ' . $impactValue),
                    'updated_at' => now(),
                ]);

            $now = now();
            $payload = [
                'id' => (string) Str::uuid(),
                'user_id' => $userId,
                'activity_id' => $activityId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($this->hasHistoryColumn('impact_category')) {
                $payload['impact_category'] = $activityType;
            } elseif ($this->hasHistoryColumn('activity_type')) {
                $payload['activity_type'] = $activityType;
            }

            if ($this->hasHistoryColumn('life_impacted')) {
                $payload['life_impacted'] = $impactValue;
            } elseif ($this->hasHistoryColumn('impact_value')) {
                $payload['impact_value'] = $impactValue;
            }

            if ($this->hasHistoryColumn('action_key')) {
                $payload['action_key'] = Str::slug($activityType, '_');
            }

            if ($this->hasHistoryColumn('action_label')) {
                $payload['action_label'] = $title;
            } elseif ($this->hasHistoryColumn('title')) {
                $payload['title'] = $title;
            }

            if ($this->hasHistoryColumn('remarks')) {
                $payload['remarks'] = $description;
            } elseif ($this->hasHistoryColumn('description')) {
                $payload['description'] = $description;
            }

            if ($this->hasHistoryColumn('counted_in_total')) {
                $payload['counted_in_total'] = true;
            }

            if ($this->hasHistoryColumn('created_by')) {
                $payload['created_by'] = $triggeredByUserId;
            } elseif ($this->hasHistoryColumn('triggered_by_user_id')) {
                $payload['triggered_by_user_id'] = $triggeredByUserId;
            }

            if ($this->hasHistoryColumn('status')) {
                $payload['status'] = 'approved';
            }

            if ($this->hasHistoryColumn('approved_at')) {
                $payload['approved_at'] = $now;
            }

            if ($this->hasHistoryColumn('meta')) {
                $payload['meta'] = $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null;
            }

            DB::table('life_impact_histories')->insert($payload);

            return $this->getCurrentTotal($userId);
        });
    }

    public function incrementAndLog(
        string $userId,
        int $points,
        string $activityType,
        string $title,
        ?string $triggeredByUserId = null,
        ?string $activityId = null,
        ?string $description = null,
        ?array $meta = null,
    ): int {
        return $this->addLifeImpact(
            $userId,
            $triggeredByUserId,
            $activityType,
            $activityId,
            (int) $points,
            $title,
            $description,
            $meta ?? []
        );
    }

    public function getCurrentTotal(string $userId): int
    {
        return (int) (DB::table('users')->where('id', $userId)->value('life_impacted_count') ?? 0);
    }

    private function hasHistoryColumn(string $column): bool
    {
        static $columns = null;

        if (! is_array($columns)) {
            $columns = Schema::getColumnListing('life_impact_histories');
        }

        return in_array($column, $columns, true);
    }
}

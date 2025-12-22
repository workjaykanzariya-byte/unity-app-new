<?php

namespace App\Support\ActivityHistory;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class HistoryQuery
{
    public function __construct(private readonly HistoryColumnResolver $resolver)
    {
    }

    public function paginate(Model $model, Request $request, ?string $filter = null): LengthAwarePaginator
    {
        $query = $model->newQuery();

        $table = $model->getTable();
        $creatorColumn = $this->resolver->resolveCreatorColumn($table);
        $receiverColumn = $this->resolver->resolveReceiverColumn($table);

        $this->applyNonDeletedConstraints($query, $table);
        $this->applyFilter($query, $filter, $creatorColumn, $receiverColumn);

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        if (Schema::hasTable($table) && Schema::hasColumn($table, 'created_at')) {
            $query->orderByDesc('created_at');
        } else {
            $query->orderByDesc($model->getKeyName());
        }

        return $query->paginate($perPage);
    }

    public function findForUser(Model $model, string $id): ?Model
    {
        $query = $model->newQuery();
        $table = $model->getTable();

        $creatorColumn = $this->resolver->resolveCreatorColumn($table);
        $receiverColumn = $this->resolver->resolveReceiverColumn($table);

        if (! $creatorColumn && ! $receiverColumn) {
            return null;
        }

        $this->applyNonDeletedConstraints($query, $table);

        $query->where($model->getKeyName() ?? 'id', $id);

        $this->applyOwnershipConstraint($query, $creatorColumn, $receiverColumn);

        return $query->first();
    }

    protected function applyFilter(Builder $query, ?string $filter, ?string $creatorColumn, ?string $receiverColumn): void
    {
        $userId = auth()->id();

        $filter = $filter ?: 'all';

        if ($filter === 'received' && $receiverColumn) {
            $query->where($receiverColumn, $userId);

            return;
        }

        if (in_array($filter, ['initiated', 'given'], true) && $creatorColumn) {
            $query->where($creatorColumn, $userId);

            return;
        }

        if ($receiverColumn && $creatorColumn) {
            $query->where(function (Builder $q) use ($creatorColumn, $receiverColumn, $userId): void {
                $q->where($creatorColumn, $userId)
                    ->orWhere($receiverColumn, $userId);
            });

            return;
        }

        if ($creatorColumn) {
            $query->where($creatorColumn, $userId);

            return;
        }

        if ($receiverColumn) {
            $query->where($receiverColumn, $userId);

            return;
        }

        $query->whereRaw('1 = 0');
    }

    protected function applyNonDeletedConstraints(Builder $query, string $table): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        if (Schema::hasColumn($table, 'is_deleted')) {
            $query->where('is_deleted', false);
        }

        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
    }

    protected function applyOwnershipConstraint(Builder $query, ?string $creatorColumn, ?string $receiverColumn): void
    {
        $userId = auth()->id();

        if ($creatorColumn && $receiverColumn) {
            $query->where(function (Builder $q) use ($creatorColumn, $receiverColumn, $userId): void {
                $q->where($creatorColumn, $userId)
                    ->orWhere($receiverColumn, $userId);
            });

            return;
        }

        if ($creatorColumn) {
            $query->where($creatorColumn, $userId);

            return;
        }

        if ($receiverColumn) {
            $query->where($receiverColumn, $userId);

            return;
        }

        $query->whereRaw('1 = 0');
    }
}

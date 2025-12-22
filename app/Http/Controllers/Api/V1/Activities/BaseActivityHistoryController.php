<?php

namespace App\Http\Controllers\Api\V1\Activities;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

abstract class BaseActivityHistoryController extends BaseApiController
{
    protected function applyFilterGivenReceived(Builder $query, string $filter, string $givenColumn, string $receivedColumn, string $userId): void
    {
        if ($filter === 'received') {
            $query->where($receivedColumn, $userId);

            return;
        }

        $query->where($givenColumn, $userId);
    }

    protected function applyNotDeletedConstraints(Builder $query, string $table): void
    {
        if (Schema::hasColumn($table, 'is_deleted')) {
            $query->where('is_deleted', false);
        }

        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
    }
}

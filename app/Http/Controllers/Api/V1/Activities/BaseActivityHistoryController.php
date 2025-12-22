<?php

namespace App\Http\Controllers\Api\V1\Activities;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\ActivityHistoryItemResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

abstract class BaseActivityHistoryController extends BaseApiController
{
    protected function resolveModel(array $candidates, string $fallbackTable): Model
    {
        foreach ($candidates as $candidate) {
            if (class_exists($candidate)) {
                return app($candidate);
            }
        }

        $model = new class extends Model {
            use \Illuminate\Database\Eloquent\Concerns\HasUuids;

            protected $guarded = [];

            public $timestamps = false;

            protected $keyType = 'string';

            public $incrementing = false;
        };

        $model->setTable($fallbackTable);

        if (Schema::hasColumn($fallbackTable, 'created_at') && Schema::hasColumn($fallbackTable, 'updated_at')) {
            $model->timestamps = true;
        }

        return $model;
    }

    protected function transformItems(array $items, Request $request): array
    {
        return collect($items)
            ->map(fn ($item) => (new ActivityHistoryItemResource($item))->toArray($request))
            ->all();
    }

    protected function buildMeta($paginator): array
    {
        return [
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }
}

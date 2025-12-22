<?php

namespace App\Http\Controllers\Api\V1\Activities;

use App\Support\ActivityHistory\HistoryQuery;
use Illuminate\Http\Request;

class RequirementHistoryController extends BaseActivityHistoryController
{
    public function index(Request $request, HistoryQuery $historyQuery)
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $model = $this->resolveModel([
            \App\Models\Requirement::class,
            \App\Models\Activities\Requirement::class,
        ], 'requirements');

        $paginator = $historyQuery->paginate($model, $request, 'initiated');

        return $this->success([
            'items' => $this->transformItems($paginator->items(), $request),
            'meta' => $this->buildMeta($paginator),
        ]);
    }
}

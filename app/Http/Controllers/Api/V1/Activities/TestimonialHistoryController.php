<?php

namespace App\Http\Controllers\Api\V1\Activities;

use App\Http\Resources\ActivityHistoryItemResource;
use App\Support\ActivityHistory\HistoryQuery;
use Illuminate\Http\Request;

class TestimonialHistoryController extends BaseActivityHistoryController
{
    public function index(Request $request, HistoryQuery $historyQuery)
    {
        $request->validate([
            'filter' => 'in:given,received,all',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $filter = $request->input('filter', 'all');

        $model = $this->resolveModel([
            \App\Models\Testimonial::class,
            \App\Models\Activities\Testimonial::class,
        ], 'testimonials');

        $paginator = $historyQuery->paginate($model, $request, $filter);

        return $this->success([
            'items' => $this->transformItems($paginator->items(), $request),
            'meta' => $this->buildMeta($paginator),
        ]);
    }

    public function show(Request $request, string $id, HistoryQuery $historyQuery)
    {
        $model = $this->resolveModel([
            \App\Models\Testimonial::class,
            \App\Models\Activities\Testimonial::class,
        ], 'testimonials');

        $record = $historyQuery->findForUser($model, $id);

        if (! $record) {
            return $this->error('Testimonial not found', 404);
        }

        return $this->success((new ActivityHistoryItemResource($record))->toArray($request));
    }
}

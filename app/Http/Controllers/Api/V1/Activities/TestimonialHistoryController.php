<?php

namespace App\Http\Controllers\Api\V1\Activities;

use App\Http\Resources\TableRowResource;
use App\Models\Testimonial;
use Illuminate\Http\Request;

class TestimonialHistoryController extends BaseActivityHistoryController
{
    private const COLUMNS = [
        'id',
        'from_user_id',
        'to_user_id',
        'content',
        'media',
        'is_deleted',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function index(Request $request)
    {
        $request->validate([
            'filter' => 'nullable|in:given,received',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $filter = $request->input('filter', 'given');
        $userId = $request->user()->id;

        $query = Testimonial::query()->select(self::COLUMNS);

        $this->applyNotDeletedConstraints($query, 'testimonials');
        $this->applyFilterGivenReceived($query, $filter, 'from_user_id', 'to_user_id', $userId);

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        if ($query->getConnection()->getSchemaBuilder()->hasColumn('testimonials', 'created_at')) {
            $query->orderByDesc('created_at');
        } else {
            $query->orderByDesc('id');
        }

        $items = TableRowResource::collection($query->get())->toArray($request);

        return $this->success([
            'items' => $items,
        ]);
    }

    public function show(Request $request, string $id)
    {
        $userId = $request->user()->id;

        $query = Testimonial::query()
            ->select(self::COLUMNS)
            ->where(function ($q) use ($userId) {
                $q->where('from_user_id', $userId)
                    ->orWhere('to_user_id', $userId);
            });

        $this->applyNotDeletedConstraints($query, 'testimonials');

        $record = $query->where('id', $id)->first();

        if (! $record) {
            return $this->error('Testimonial not found', 404);
        }

        return $this->success((new TableRowResource($record))->toArray($request));
    }
}

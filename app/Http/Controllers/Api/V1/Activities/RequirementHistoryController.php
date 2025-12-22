<?php

namespace App\Http\Controllers\Api\V1\Activities;

use App\Http\Resources\TableRowResource;
use App\Models\Requirement;
use Illuminate\Http\Request;

class RequirementHistoryController extends BaseActivityHistoryController
{
    private const COLUMNS = [
        'id',
        'user_id',
        'subject',
        'description',
        'media',
        'region_filter',
        'category_filter',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function index(Request $request)
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $userId = $request->user()->id;

        $query = Requirement::query()
            ->select(self::COLUMNS)
            ->where('user_id', $userId);

        $this->applyNotDeletedConstraints($query, 'requirements');

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        if ($query->getConnection()->getSchemaBuilder()->hasColumn('requirements', 'created_at')) {
            $query->orderByDesc('created_at');
        } else {
            $query->orderByDesc('id');
        }

        $items = TableRowResource::collection($query->get())->toArray($request);

        return $this->success([
            'items' => $items,
        ]);
    }
}

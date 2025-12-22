<?php

namespace App\Http\Controllers\Api\V1\Activities;

use App\Http\Resources\TableRowResource;
use App\Models\BusinessDeal;
use App\Support\ActivityHistory\HistoryPaginator;
use Illuminate\Http\Request;

class BusinessDealHistoryController extends BaseActivityHistoryController
{
    private const COLUMNS = [
        'id',
        'from_user_id',
        'to_user_id',
        'deal_date',
        'deal_amount',
        'business_type',
        'comment',
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

        $query = BusinessDeal::query()->select(self::COLUMNS);

        $this->applyNotDeletedConstraints($query, 'business_deals');
        $this->applyFilterGivenReceived($query, $filter, 'from_user_id', 'to_user_id', $userId);

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        if ($query->getConnection()->getSchemaBuilder()->hasColumn('business_deals', 'created_at')) {
            $query->orderByDesc('created_at');
        } else {
            $query->orderByDesc('id');
        }

        $paginator = $query->paginate($perPage);

        $items = TableRowResource::collection($paginator->getCollection())->toArray($request);

        return $this->success([
            'items' => $items,
            'meta' => HistoryPaginator::meta($paginator),
        ]);
    }

    public function show(Request $request, string $id)
    {
        $userId = $request->user()->id;

        $query = BusinessDeal::query()
            ->select(self::COLUMNS)
            ->where(function ($q) use ($userId) {
                $q->where('from_user_id', $userId)
                    ->orWhere('to_user_id', $userId);
            });

        $this->applyNotDeletedConstraints($query, 'business_deals');

        $record = $query->where('id', $id)->first();

        if (! $record) {
            return $this->error('Business deal not found', 404);
        }

        return $this->success((new TableRowResource($record))->toArray($request));
    }
}

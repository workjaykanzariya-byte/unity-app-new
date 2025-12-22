<?php

namespace App\Http\Controllers\Api\V1\Activities;

use App\Http\Resources\TableRowResource;
use App\Models\P2pMeeting;
use App\Support\ActivityHistory\HistoryPaginator;
use Illuminate\Http\Request;

class P2pMeetingHistoryController extends BaseActivityHistoryController
{
    private const COLUMNS = [
        'id',
        'initiator_user_id',
        'peer_user_id',
        'meeting_date',
        'meeting_place',
        'remarks',
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

        $query = P2pMeeting::query()->select(self::COLUMNS);

        $this->applyNotDeletedConstraints($query, 'p2p_meetings');
        $this->applyFilterGivenReceived($query, $filter, 'initiator_user_id', 'peer_user_id', $userId);

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        if ($query->getConnection()->getSchemaBuilder()->hasColumn('p2p_meetings', 'created_at')) {
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
}

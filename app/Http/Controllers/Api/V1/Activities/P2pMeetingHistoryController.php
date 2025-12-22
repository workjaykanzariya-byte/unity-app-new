<?php

namespace App\Http\Controllers\Api\V1\Activities;

use App\Support\ActivityHistory\HistoryQuery;
use Illuminate\Http\Request;

class P2pMeetingHistoryController extends BaseActivityHistoryController
{
    public function index(Request $request, HistoryQuery $historyQuery)
    {
        $request->validate([
            'filter' => 'in:initiated,received',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $filter = $request->input('filter', 'initiated');

        $model = $this->resolveModel([
            \App\Models\P2pMeeting::class,
            \App\Models\P2PMeeting::class,
            \App\Models\Activities\P2pMeeting::class,
        ], 'p2p_meetings');

        $paginator = $historyQuery->paginate($model, $request, $filter);

        return $this->success([
            'items' => $this->transformItems($paginator->items(), $request),
            'meta' => $this->buildMeta($paginator),
        ]);
    }
}

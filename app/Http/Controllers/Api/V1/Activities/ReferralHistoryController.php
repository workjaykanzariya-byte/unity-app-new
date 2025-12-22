<?php

namespace App\Http\Controllers\Api\V1\Activities;

use App\Support\ActivityHistory\HistoryQuery;
use Illuminate\Http\Request;

class ReferralHistoryController extends BaseActivityHistoryController
{
    public function index(Request $request, HistoryQuery $historyQuery)
    {
        $request->validate([
            'filter' => 'in:given,received,all',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $filter = $request->input('filter', 'all');

        $model = $this->resolveModel([
            \App\Models\Referral::class,
            \App\Models\Activities\Referral::class,
        ], 'referrals');

        $paginator = $historyQuery->paginate($model, $request, $filter);

        return $this->success([
            'items' => $this->transformItems($paginator->items(), $request),
            'meta' => $this->buildMeta($paginator),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Activities;

use App\Http\Resources\TableRowResource;
use App\Models\Referral;
use Illuminate\Http\Request;

class ReferralHistoryController extends BaseActivityHistoryController
{
    private const COLUMNS = [
        'id',
        'from_user_id',
        'to_user_id',
        'referral_type',
        'referral_date',
        'referral_of',
        'phone',
        'email',
        'address',
        'hot_value',
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

        $query = Referral::query()->select(self::COLUMNS);

        $this->applyNotDeletedConstraints($query, 'referrals');
        $this->applyFilterGivenReceived($query, $filter, 'from_user_id', 'to_user_id', $userId);

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        if ($query->getConnection()->getSchemaBuilder()->hasColumn('referrals', 'created_at')) {
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

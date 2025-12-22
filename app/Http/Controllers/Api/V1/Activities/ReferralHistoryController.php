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
            'filter' => 'nullable|in:given,received,all',
            'debug' => 'nullable|in:1',
        ]);

        $filter = $request->input('filter', 'all');
        $userId = $request->user()->id;

        $query = Referral::query()->select(self::COLUMNS);

        $this->applyNotDeletedConstraints($query, 'referrals');
        $whereDescription = '';
        $this->applyFilter($query, $filter, $userId, $whereDescription);

        if ($query->getConnection()->getSchemaBuilder()->hasColumn('referrals', 'created_at')) {
            $query->orderByDesc('created_at');
        } else {
            $query->orderByDesc('id');
        }

        $items = TableRowResource::collection($query->get())->toArray($request);

        $data = ['items' => $items];

        if ($request->boolean('debug')) {
            $data['debug'] = [
                'auth_user_id' => $userId,
                'filter' => $filter,
                'where' => $whereDescription,
            ];
        }

        return $this->success($data);
    }

    protected function applyFilter($query, string $filter, string $userId, string &$whereDescription): void
    {
        if ($filter === 'given') {
            $query->where('from_user_id', $userId);
            $whereDescription = "from_user_id={$userId}";

            return;
        }

        if ($filter === 'received') {
            $query->where('to_user_id', $userId);
            $whereDescription = "to_user_id={$userId}";

            return;
        }

        $query->where(function ($q) use ($userId) {
            $q->where('from_user_id', $userId)
                ->orWhere('to_user_id', $userId);
        });

        $whereDescription = "from_user_id={$userId} OR to_user_id={$userId}";
    }
}

<?php

namespace App\Http\Controllers\Api\Activities;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\TableRowResource;
use App\Models\Referral;
use Illuminate\Http\Request;

class ReferralHistoryController extends BaseApiController
{
    public function index(Request $request)
    {
        $authUserId = $request->user()->id;
        $filter = $request->query('filter', 'all');
        $debugMode = $request->boolean('debug');

        $query = Referral::query();
        $whereParts = [];

        $query->where(function ($q) use (&$whereParts) {
            $q->where('is_deleted', false)
                ->orWhereNull('is_deleted');

            $whereParts[] = '(is_deleted = false OR is_deleted IS NULL)';
        });

        $query->whereNull('deleted_at');
        $whereParts[] = 'deleted_at IS NULL';

        if ($filter === 'given') {
            $query->where('from_user_id', $authUserId);
            $whereParts[] = 'from_user_id = "' . $authUserId . '"';
        } elseif ($filter === 'received') {
            $query->where('to_user_id', $authUserId);
            $whereParts[] = 'to_user_id = "' . $authUserId . '"';
        } else {
            $query->where(function ($q) use ($authUserId, &$whereParts) {
                $q->where('from_user_id', $authUserId)
                    ->orWhere('to_user_id', $authUserId);

                $whereParts[] = '(from_user_id = "' . $authUserId . '" OR to_user_id = "' . $authUserId . '")';
            });
            $filter = 'all';
        }

        $items = TableRowResource::collection(
            $query
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get()
        );

        $response = [
            'items' => $items,
        ];

        if ($debugMode) {
            $response['debug'] = [
                'auth_user_id' => $authUserId,
                'filter' => $filter,
                'where' => implode(' AND ', $whereParts),
            ];
        }

        return $this->success($response);
    }
}

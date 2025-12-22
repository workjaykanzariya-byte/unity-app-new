<?php

namespace App\Http\Controllers\Api\Activities;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\TableRowResource;
use App\Models\BusinessDeal;
use Illuminate\Http\Request;

class BusinessDealHistoryController extends BaseApiController
{
    public function index(Request $request)
    {
        $authUserId = $request->user()->id;
        $filter = $request->query('filter', 'given');
        $debugMode = $request->boolean('debug');

        $query = BusinessDeal::query();
        $whereParts = [];

        $query->where(function ($q) use (&$whereParts) {
            $q->where('is_deleted', false)
                ->orWhereNull('is_deleted');

            $whereParts[] = '(is_deleted = false OR is_deleted IS NULL)';
        });

        $query->whereNull('deleted_at');
        $whereParts[] = 'deleted_at IS NULL';

        if ($filter === 'received') {
            $query->where('to_user_id', $authUserId);
            $whereParts[] = 'to_user_id = "' . $authUserId . '"';
        } else {
            $query->where('from_user_id', $authUserId);
            $whereParts[] = 'from_user_id = "' . $authUserId . '"';
            $filter = 'given';
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

    public function show(Request $request, string $id)
    {
        $authUserId = $request->user()->id;
        $debugMode = $request->boolean('debug');
        $filterUsed = 'all';
        $whereParts = [];

        $query = BusinessDeal::query();

        $query->where('id', $id);
        $whereParts[] = 'id = "' . $id . '"';

        $query->where(function ($q) use (&$whereParts) {
            $q->where('is_deleted', false)
                ->orWhereNull('is_deleted');

            $whereParts[] = '(is_deleted = false OR is_deleted IS NULL)';
        });

        $query->whereNull('deleted_at');
        $whereParts[] = 'deleted_at IS NULL';

        $query->where(function ($q) use ($authUserId, &$whereParts) {
            $q->where('from_user_id', $authUserId)
                ->orWhere('to_user_id', $authUserId);

            $whereParts[] = '(from_user_id = "' . $authUserId . '" OR to_user_id = "' . $authUserId . '")';
        });

        $businessDeal = $query->first();

        if (! $businessDeal) {
            return $this->error('Business deal not found', 404);
        }

        $response = TableRowResource::make($businessDeal);

        if ($debugMode) {
            $response = [
                'item' => $response,
                'debug' => [
                    'auth_user_id' => $authUserId,
                    'filter' => $filterUsed,
                    'where' => implode(' AND ', $whereParts),
                ],
            ];
        }

        return $this->success($response);
    }
}

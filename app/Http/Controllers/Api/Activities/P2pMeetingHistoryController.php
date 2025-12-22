<?php

namespace App\Http\Controllers\Api\Activities;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\TableRowResource;
use App\Models\P2pMeeting;
use Illuminate\Http\Request;

class P2pMeetingHistoryController extends BaseApiController
{
    public function index(Request $request)
    {
        $authUserId = $request->user()->id;
        $filter = $request->query('filter', 'given');
        $debugMode = $request->boolean('debug');

        $query = P2pMeeting::query();

        $whereParts = [];

        $query->where(function ($q) use (&$whereParts) {
            $q->where('is_deleted', false)
                ->orWhereNull('is_deleted');

            $whereParts[] = '(is_deleted = false OR is_deleted IS NULL)';
        });

        $query->whereNull('deleted_at');
        $whereParts[] = 'deleted_at IS NULL';

        if ($filter === 'received') {
            $query->where('peer_user_id', $authUserId);
            $whereParts[] = 'peer_user_id = "' . $authUserId . '"';
        } else {
            $query->where('initiator_user_id', $authUserId);
            $whereParts[] = 'initiator_user_id = "' . $authUserId . '"';
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
}

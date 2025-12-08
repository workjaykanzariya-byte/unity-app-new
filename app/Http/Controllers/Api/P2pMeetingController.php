<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Activity\StoreP2pMeetingRequest;
use App\Models\P2pMeeting;
use App\Services\Coins\CoinsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Throwable;

class P2pMeetingController extends BaseApiController
{
    public function index(Request $request)
    {
        $authUser = $request->user();
        $filter = $request->input('filter', 'initiated');

        $query = P2pMeeting::query()
            ->where('is_deleted', false)
            ->whereNull('deleted_at');

        if ($filter === 'received') {
            $query->where('peer_user_id', $authUser->id);
        } elseif ($filter === 'all') {
            $query->where(function ($q) use ($authUser) {
                $q->where('initiator_user_id', $authUser->id)
                    ->orWhere('peer_user_id', $authUser->id);
            });
        } else {
            $query->where('initiator_user_id', $authUser->id);
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        $paginator = $query
            ->orderBy('meeting_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->success([
            'items' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreP2pMeetingRequest $request, CoinsService $coinsService)
    {
        $authUser = $request->user();

        try {
            [$meeting, $newBalance] = DB::transaction(function () use ($request, $authUser, $coinsService) {
                $createdMeeting = P2pMeeting::create([
                    'initiator_user_id' => $authUser->id,
                    'peer_user_id' => $request->input('peer_user_id'),
                    'meeting_date' => $request->input('meeting_date'),
                    'meeting_place' => $request->input('meeting_place'),
                    'remarks' => $request->input('remarks'),
                    'is_deleted' => false,
                ]);

                $balance = $coinsService->awardForActivity($authUser, 'p2p-meetings', (string) $createdMeeting->id);

                return [$createdMeeting, $balance];
            });

            return $this->success(
                array_merge($meeting->toArray(), ['coins_balance' => $newBalance]),
                'P2P meeting saved successfully',
                201
            );
        } catch (Throwable $e) {
            return $this->error('Something went wrong', 500);
        }
    }

    public function show(Request $request, string $id)
    {
        $authUser = $request->user();

        $meeting = P2pMeeting::where('id', $id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->where(function ($q) use ($authUser) {
                $q->where('initiator_user_id', $authUser->id)
                    ->orWhere('peer_user_id', $authUser->id);
            })
            ->first();

        if (! $meeting) {
            return $this->error('P2P meeting not found', 404);
        }

        return $this->success($meeting);
    }
}

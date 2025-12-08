<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Activity\StoreP2pMeetingRequest;
use App\Models\P2pMeeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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

    public function store(StoreP2pMeetingRequest $request)
    {
        $authUser = $request->user();

        try {
            $meeting = P2pMeeting::create([
                'initiator_user_id' => $authUser->id,
                'peer_user_id' => $request->input('peer_user_id'),
                'meeting_date' => $request->input('meeting_date'),
                'meeting_place' => $request->input('meeting_place'),
                'remarks' => $request->input('remarks'),
                'is_deleted' => false,
            ]);

            $coinMap = [
                'p2p_meetings' => 1000,
                'requirements' => 3000,
                'referrals' => 3000,
                'business_deals' => 15000,
                'testimonials' => 5000,
            ];

            $reference = 'p2p_meetings';
            $coins = $coinMap[$reference];
            $coinsEarned = $coins;
            $newBalance = $authUser->coins_balance;

            try {
                DB::transaction(function () use ($authUser, $meeting, $reference, $coins, &$newBalance) {
                    $userRow = DB::table('users')
                        ->where('id', $authUser->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $userRow) {
                        throw new \RuntimeException('User not found during coin credit');
                    }

                    $newBalance = (int) $userRow->coins_balance + (int) $coins;

                    DB::table('users')
                        ->where('id', $authUser->id)
                        ->update([
                            'coins_balance' => $newBalance,
                        ]);

                    DB::table('coins_ledger')->insert([
                        'transaction_id' => Str::uuid()->toString(),
                        'user_id' => $authUser->id,
                        'amount' => $coins,
                        'balance_after' => $newBalance,
                        'activity_id' => $meeting->id,
                        'reference' => $reference,
                        'created_by' => $authUser->id,
                        'created_at' => now(),
                    ]);
                });
            } catch (Throwable $e) {
                Log::error('COIN CREDIT FAILED', [
                    'error' => $e->getMessage(),
                    'reference' => $reference,
                    'activity_id' => $meeting->id,
                    'user_id' => $authUser->id,
                ]);

                $coinsEarned = 0;
                $newBalance = $authUser->coins_balance;
            }

            $payload = $meeting->toArray();
            $payload['coins_earned'] = $coinsEarned;
            $payload['total_coins'] = $newBalance;

            return $this->success($payload, 'P2P meeting saved successfully', 201);
        } catch (Throwable $e) {
            Log::error('P2P meeting store error', [
                'user_id' => $authUser->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

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

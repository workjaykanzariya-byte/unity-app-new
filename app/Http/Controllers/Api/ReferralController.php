<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Activity\StoreReferralRequest;
use App\Models\Referral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ReferralController extends BaseApiController
{
    public function index(Request $request)
    {
        $authUser = $request->user();
        $filter = $request->input('filter', 'given');
        $referralType = $request->input('referral_type');

        $query = Referral::query()
            ->where('is_deleted', false)
            ->whereNull('deleted_at');

        if ($filter === 'received') {
            $query->where('to_user_id', $authUser->id);
        } elseif ($filter === 'all') {
            $query->where(function ($q) use ($authUser) {
                $q->where('from_user_id', $authUser->id)
                    ->orWhere('to_user_id', $authUser->id);
            });
        } else {
            $query->where('from_user_id', $authUser->id);
        }

        if ($referralType) {
            $query->where('referral_type', $referralType);
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        $paginator = $query
            ->orderBy('referral_date', 'desc')
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

    public function store(StoreReferralRequest $request)
    {
        $authUser = $request->user();

        try {
            $referral = Referral::create([
                'from_user_id' => $authUser->id,
                'to_user_id' => $request->input('to_user_id'),
                'referral_type' => $request->input('referral_type'),
                'referral_date' => $request->input('referral_date'),
                'referral_of' => $request->input('referral_of'),
                'phone' => $request->input('phone'),
                'email' => $request->input('email'),
                'address' => $request->input('address'),
                'hot_value' => $request->input('hot_value'),
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

            $reference = 'referrals';
            $coins = $coinMap[$reference];
            $coinsEarned = $coins;
            $newBalance = $authUser->coins_balance;

            try {
                DB::transaction(function () use ($authUser, $referral, $reference, $coins, &$newBalance) {
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
                        'activity_id' => $referral->id,
                        'reference' => $reference,
                        'created_by' => $authUser->id,
                        'created_at' => now(),
                    ]);
                });
            } catch (Throwable $e) {
                Log::error('COIN CREDIT FAILED', [
                    'error' => $e->getMessage(),
                    'reference' => $reference,
                    'activity_id' => $referral->id,
                    'user_id' => $authUser->id,
                ]);

                $coinsEarned = 0;
                $newBalance = $authUser->coins_balance;
            }

            $payload = $referral->toArray();
            $payload['coins_earned'] = $coinsEarned;
            $payload['total_coins'] = $newBalance;

            return $this->success($payload, 'Referral saved successfully', 201);
        } catch (Throwable $e) {
            Log::error('Referral store error', [
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

        $referral = Referral::where('id', $id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->where(function ($q) use ($authUser) {
                $q->where('from_user_id', $authUser->id)
                    ->orWhere('to_user_id', $authUser->id);
            })
            ->first();

        if (! $referral) {
            return $this->error('Referral not found', 404);
        }

        return $this->success($referral);
    }
}

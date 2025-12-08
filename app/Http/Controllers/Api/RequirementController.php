<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Activity\StoreRequirementRequest;
use App\Models\Requirement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class RequirementController extends BaseApiController
{
    public function index(Request $request)
    {
        $authUser = $request->user();
        $status = $request->input('status');

        $query = Requirement::query()
            ->where('user_id', $authUser->id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at');

        if ($status) {
            $query->where('status', $status);
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        $paginator = $query
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

    public function store(StoreRequirementRequest $request)
    {
        $authUser = $request->user();

        $media = null;
        if ($request->filled('media_id')) {
            $media = [[
                'id' => $request->input('media_id'),
                'type' => 'image',
            ]];
        }

        try {
            $requirement = Requirement::create([
                'user_id' => $authUser->id,
                'subject' => $request->input('subject'),
                'description' => $request->input('description'),
                'media' => $media,
                'region_label' => $request->input('region_label'),
                'city_name' => $request->input('city_name'),
                'category' => $request->input('category'),
                'status' => $request->input('status', 'open') ?: 'open',
                'is_deleted' => false,
            ]);

            $coinMap = [
                'p2p_meetings' => 1000,
                'requirements' => 3000,
                'referrals' => 3000,
                'business_deals' => 15000,
                'testimonials' => 5000,
            ];

            $reference = 'requirements';
            $coins = $coinMap[$reference];
            $coinsEarned = $coins;
            $newBalance = $authUser->coins_balance;

            try {
                DB::transaction(function () use ($authUser, $requirement, $reference, $coins, &$newBalance) {
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
                        'activity_id' => $requirement->id,
                        'reference' => $reference,
                        'created_by' => $authUser->id,
                        'created_at' => now(),
                    ]);
                });
            } catch (Throwable $e) {
                Log::error('COIN CREDIT FAILED', [
                    'error' => $e->getMessage(),
                    'reference' => $reference,
                    'activity_id' => $requirement->id,
                    'user_id' => $authUser->id,
                ]);

                $coinsEarned = 0;
                $newBalance = $authUser->coins_balance;
            }

            $payload = $requirement->toArray();
            $payload['coins_earned'] = $coinsEarned;
            $payload['total_coins'] = $newBalance;

            return $this->success($payload, 'Requirement created successfully', 201);
        } catch (Throwable $e) {
            Log::error('Requirement store error', [
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

        $requirement = Requirement::where('id', $id)
            ->where('user_id', $authUser->id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->first();

        if (! $requirement) {
            return $this->error('Requirement not found', 404);
        }

        return $this->success($requirement);
    }
}

<?php

namespace App\Http\Controllers\Api\Activities;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Activities\StoreRequirementRequest;
use App\Http\Resources\RequirementResource;
use App\Models\Requirement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class RequirementController extends BaseApiController
{
    public function store(StoreRequirementRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        $media = null;
        if (! empty($data['media_id'])) {
            $media = [[
                'id' => $data['media_id'],
                'type' => 'image',
            ]];
        }

        try {
            $regionFilter = [
                'region_label' => $data['region_label'],
                'city_name' => $data['city_name'],
            ];

            $categoryFilter = [
                'category' => $data['category'],
            ];

            $requirement = Requirement::create([
                'user_id' => $user->id,
                'subject' => $data['subject'],
                'description' => $data['description'],
                'media' => $media,
                'region_filter' => $regionFilter,
                'category_filter' => $categoryFilter,
                'status' => $data['status'] ?? 'open',
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
            $newBalance = $user->coins_balance;

            DB::beginTransaction();

            try {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'coins_balance' => DB::raw("coins_balance + {$coins}"),
                    ]);

                $updated = DB::table('users')->where('id', $user->id)->first();
                $newBalance = $updated->coins_balance;

                DB::table('coins_ledger')->insert([
                    'transaction_id' => Str::uuid()->toString(),
                    'user_id' => $user->id,
                    'amount' => $coins,
                    'balance_after' => $newBalance,
                    'activity_id' => $requirement->id,
                    'reference' => $reference,
                    'created_by' => $user->id,
                    'created_at' => now(),
                ]);

                DB::commit();
            } catch (Throwable $e) {
                DB::rollBack();
                Log::error('COIN CREDIT FAILED', [
                    'error' => $e->getMessage(),
                    'reference' => $reference,
                    'activity_id' => $requirement->id,
                    'user_id' => $user->id,
                ]);

                $coins = 0;
                $newBalance = $user->coins_balance;
            }

            $payload = [
                'requirement' => new RequirementResource($requirement),
                'coins_earned' => $coins,
                'total_coins' => $newBalance,
            ];

            return $this->success(
                $payload,
                'Requirement created successfully',
                201
            );
        } catch (Throwable $e) {
            Log::error('Create requirement failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error('Failed to create requirement', 500);
        }
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $query = Requirement::where('user_id', $user->id)
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        $paginator = $query->paginate($perPage);

        return $this->success([
            'items' => RequirementResource::collection($paginator),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(Request $request, string $id)
    {
        $user = $request->user();

        $requirement = Requirement::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $requirement) {
            return $this->error('Requirement not found', 404);
        }

        return $this->success(new RequirementResource($requirement));
    }
}

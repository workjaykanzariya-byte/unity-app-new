<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Activity\StoreBusinessDealRequest;
use App\Models\BusinessDeal;
use App\Services\Coins\CoinsService;
use Illuminate\Http\Request;
use Throwable;

class BusinessDealController extends BaseApiController
{
    public function index(Request $request)
    {
        $authUser = $request->user();
        $filter = $request->input('filter', 'given');
        $businessType = $request->input('business_type');

        $query = BusinessDeal::query()
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

        if ($businessType) {
            $query->where('business_type', $businessType);
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        $paginator = $query
            ->orderBy('deal_date', 'desc')
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

    public function store(StoreBusinessDealRequest $request)
    {
        $authUser = $request->user();

        try {
            $businessDeal = BusinessDeal::create([
                'from_user_id' => $authUser->id,
                'to_user_id' => $request->input('to_user_id'),
                'deal_date' => $request->input('deal_date'),
                'deal_amount' => $request->input('deal_amount'),
                'business_type' => $request->input('business_type'),
                'comment' => $request->input('comment'),
                'is_deleted' => false,
            ]);

            $coinsLedger = app(CoinsService::class)->rewardForActivity(
                $authUser,
                'business_deal',
                $businessDeal->id ?? null,
                'Activity: business_deal',
                $authUser->id
            );

            if ($coinsLedger) {
                $businessDeal->setAttribute('coins', [
                    'earned' => $coinsLedger->amount,
                    'balance_after' => $coinsLedger->balance_after,
                ]);
            }

            return $this->success($businessDeal, 'Business deal saved successfully', 201);
        } catch (Throwable $e) {
            return $this->error('Something went wrong', 500);
        }
    }

    public function show(Request $request, string $id)
    {
        $authUser = $request->user();

        $businessDeal = BusinessDeal::where('id', $id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->where(function ($q) use ($authUser) {
                $q->where('from_user_id', $authUser->id)
                    ->orWhere('to_user_id', $authUser->id);
            })
            ->first();

        if (! $businessDeal) {
            return $this->error('Business deal not found', 404);
        }

        return $this->success($businessDeal);
    }
}

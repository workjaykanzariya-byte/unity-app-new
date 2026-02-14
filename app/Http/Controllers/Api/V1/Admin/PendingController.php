<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\CoinClaimRequestResource;
use App\Models\CoinClaimRequest;
use App\Models\VisitorRegistration;
use App\Support\AdminCircleScope;
use Illuminate\Http\Request;

class PendingController extends BaseApiController
{
    public function summary(Request $request)
    {
        $admin = auth('admin')->user();

        $visitorQuery = VisitorRegistration::query()->where('status', 'pending');
        AdminCircleScope::applyToActivityQuery($visitorQuery, $admin, 'visitor_registrations.user_id', null);

        $coinClaimQuery = CoinClaimRequest::query()->where('status', 'pending');
        AdminCircleScope::applyToActivityQuery($coinClaimQuery, $admin, 'coin_claim_requests.user_id', null);

        $visitorCount = $visitorQuery->count();
        $coinClaimsCount = $coinClaimQuery->count();

        return $this->success([
            'visitor_registrations_pending_count' => $visitorCount,
            'coin_claims_pending_count' => $coinClaimsCount,
            'total_pending_count' => $visitorCount + $coinClaimsCount,
        ]);
    }

    public function items(Request $request)
    {
        $type = $request->query('type', 'coin_claims');
        $status = $request->query('status', 'pending');
        $perPage = min((int) $request->query('per_page', 20), 100);
        $admin = auth('admin')->user();

        if ($type === 'visitor_registrations') {
            $query = VisitorRegistration::query()->with('user:id,display_name,first_name,last_name,phone');
            if ($status !== 'all') {
                $query->where('status', $status);
            }
            AdminCircleScope::applyToActivityQuery($query, $admin, 'visitor_registrations.user_id', null);
            $paginator = $query->orderByDesc('created_at')->paginate($perPage);

            return $this->success([
                'type' => $type,
                'items' => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ]);
        }

        $query = CoinClaimRequest::query()->with('user:id,display_name,first_name,last_name,phone');
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        AdminCircleScope::applyToActivityQuery($query, $admin, 'coin_claim_requests.user_id', null);
        $paginator = $query->orderByDesc('created_at')->paginate($perPage);

        return $this->success([
            'type' => $type,
            'items' => CoinClaimRequestResource::collection($paginator),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}

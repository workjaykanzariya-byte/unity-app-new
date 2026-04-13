<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Impacts\LifeImpactHistoryRequest;
use App\Http\Requests\Impacts\StoreLifeImpactRequest;
use App\Http\Resources\LifeImpactResource;
use App\Models\Impact;
use App\Models\User;
use App\Services\Impacts\LifeImpactActionCatalog;
use App\Services\Impacts\LifeImpactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LifeImpactController extends BaseApiController
{
    public function __construct(
        private readonly LifeImpactService $lifeImpactService,
        private readonly LifeImpactActionCatalog $catalog,
    ) {
    }

    public function actions(): JsonResponse
    {
        return $this->success([
            'items' => $this->catalog->toList()->all(),
        ]);
    }

    public function store(StoreLifeImpactRequest $request): JsonResponse
    {
        $impact = $this->lifeImpactService->create($request->user(), $request->validated());

        return $this->success(
            new LifeImpactResource($impact),
            'Life impact activity created successfully.',
            201
        );
    }

    public function history(LifeImpactHistoryRequest $request): JsonResponse
    {
        $user = $request->user();
        $filters = $request->validated();
        $perPage = (int) ($filters['per_page'] ?? 15);

        $query = Impact::query()
            ->where('user_id', $user->id)
            ->whereIn('action', $this->catalog->keys())
            ->orderByDesc('created_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['action_key'])) {
            $query->where('action', $filters['action_key']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('impact_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('impact_date', '<=', $filters['date_to']);
        }

        $histories = $query->paginate($perPage);

        return $this->success([
            'items' => LifeImpactResource::collection($histories->getCollection())->resolve(),
            'pagination' => [
                'current_page' => $histories->currentPage(),
                'per_page' => $histories->perPage(),
                'total' => $histories->total(),
                'last_page' => $histories->lastPage(),
            ],
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $viewer = $request->user();
        $targetUserId = (string) $request->query('user_id', (string) $viewer->id);

        /** @var User $targetUser */
        $targetUser = User::query()->findOrFail($targetUserId);

        $allowedKeys = $this->catalog->keys();

        $baseQuery = Impact::query()
            ->where('user_id', $targetUser->id)
            ->whereIn('action', $allowedKeys);

        $approvedSum = (int) (clone $baseQuery)
            ->where('status', 'approved')
            ->sum('life_impacted');

        $pendingSum = (int) (clone $baseQuery)
            ->where('status', 'pending')
            ->sum('life_impacted');

        $totalRecords = (int) (clone $baseQuery)->count();
        $latest = (clone $baseQuery)->latest('created_at')->first();

        $syncedTotal = $this->lifeImpactService->recalculateTotalForUser($targetUser->id);

        Log::info('life_impact.summary_requested', [
            'requested_by' => (string) $viewer->id,
            'user_id' => (string) $targetUser->id,
            'total_records' => $totalRecords,
            'approved_life_impacted' => $approvedSum,
            'pending_life_impacted' => $pendingSum,
        ]);

        return $this->success([
            'user_id' => (string) $targetUser->id,
            'total_life_impacted' => $syncedTotal,
            'approved_life_impacted' => $approvedSum,
            'pending_life_impacted' => $pendingSum,
            'total_records' => $totalRecords,
            'latest_activity' => $latest ? [
                'id' => (string) $latest->id,
                'action_label' => $this->catalog->get((string) $latest->action)['label'] ?? (string) $latest->action,
                'life_impacted' => (int) ($latest->life_impacted ?? 1),
                'created_at' => optional($latest->created_at)?->toISOString(),
            ] : null,
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $impact = Impact::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->whereIn('action', $this->catalog->keys())
            ->firstOrFail();

        return $this->success(new LifeImpactResource($impact));
    }
}

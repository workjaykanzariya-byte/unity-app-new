<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Impacts\LifeImpactHistoryRequest;
use App\Http\Resources\LifeImpactHistoryResource;
use App\Models\LifeImpactHistory;
use App\Services\LifeImpact\LifeImpactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LifeImpactController extends BaseApiController
{
    public function __construct(private readonly LifeImpactService $lifeImpactService)
    {
    }

    public function actions(): JsonResponse
    {
        return $this->success([
            'items' => $this->lifeImpactService->actions(),
        ]);
    }

    public function history(LifeImpactHistoryRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $perPage = (int) ($filters['per_page'] ?? 15);

        Log::info('life_impact.history_requested', [
            'user_id' => (string) $request->user()->id,
            'filters' => $filters,
        ]);

        $query = $this->lifeImpactService->historyQueryForUser($request->user());

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['action_key'])) {
            $query->where('action_key', $filters['action_key']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $items = $query->paginate($perPage);

        return $this->success([
            'items' => LifeImpactHistoryResource::collection($items->getCollection())->resolve(),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'last_page' => $items->lastPage(),
            ],
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        Log::info('life_impact.summary_requested', [
            'user_id' => (string) $request->user()->id,
        ]);

        return $this->success($this->lifeImpactService->summaryForUser($request->user()));
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $item = LifeImpactHistory::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return $this->success(new LifeImpactHistoryResource($item));
    }
}

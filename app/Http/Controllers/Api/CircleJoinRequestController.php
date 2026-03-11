<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\CircleJoinRequests\ListMyCircleJoinRequests;
use App\Http\Requests\Api\CircleJoinRequests\StoreCircleJoinRequest;
use App\Models\Circle;
use App\Models\CircleJoinRequest;
use App\Services\Circles\CircleJoinRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CircleJoinRequestController extends BaseApiController
{
    public function __construct(private readonly CircleJoinRequestService $service)
    {
    }

    public function store(StoreCircleJoinRequest $request): JsonResponse
    {
        $circle = Circle::query()->where('id', $request->validated('circle_id'))->firstOrFail();

        if ($circle->status !== 'active') {
            return $this->error('Circle is not active.', 422);
        }

        try {
            $record = $this->service->submitRequest($request->user(), $circle, $request->validated('reason_for_joining'));

            return $this->success($record->load(['circle:id,name', 'user:id,display_name,email,phone,company_name,city']), 'Circle join request submitted successfully.', 201);
        } catch (ValidationException $exception) {
            return $this->error('Validation failed.', 422, $exception->errors());
        }
    }

    public function myRequests(ListMyCircleJoinRequests $request): JsonResponse
    {
        $status = $request->validated('status');

        $items = CircleJoinRequest::query()
            ->where('user_id', $request->user()->id)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with(['circle:id,name'])
            ->latest('created_at')
            ->paginate(20);

        return $this->success([
            'items' => $items->items(),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $record = CircleJoinRequest::query()->with(['circle', 'user', 'cdApprovedBy', 'cdRejectedBy', 'idApprovedBy', 'idRejectedBy'])->findOrFail($id);

        if ((string) $record->user_id !== (string) $request->user()->id) {
            return $this->error('Forbidden.', 403);
        }

        return $this->success($record);
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $record = CircleJoinRequest::query()->findOrFail($id);

        try {
            $updated = $this->service->cancelByUser($record, $request->user());

            return $this->success($updated, 'Circle join request cancelled successfully.');
        } catch (ValidationException $exception) {
            return $this->error('Validation failed.', 422, $exception->errors());
        }
    }
}

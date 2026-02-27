<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Requirements\CloseRequirementRequest;
use App\Http\Requests\Requirements\StoreRequirementRequest;
use App\Http\Resources\Requirement\RequirementDetailResource;
use App\Models\Requirement;
use App\Services\Requirements\RequirementNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class RequirementController extends Controller
{
    public function __construct(private readonly RequirementNotificationService $requirementNotificationService)
    {
    }

    public function store(StoreRequirementRequest $request): JsonResponse
    {
        $user = $request->user();
        $payload = $request->validated();

        $media = collect($payload['media'] ?? [])->map(function ($item) {
            return [
                'type' => data_get($item, 'type', 'image'),
                'file_id' => data_get($item, 'file_id'),
            ];
        })->values()->all();

        $requirement = Requirement::create([
            'user_id' => $user->id,
            'subject' => $payload['subject'],
            'description' => $payload['description'] ?? null,
            'media' => $media,
            'region_filter' => $payload['region_filter'] ?? [],
            'category_filter' => $payload['category_filter'] ?? [],
            'status' => 'open',
        ]);

        $requirement->load('user');

        try {
            $this->requirementNotificationService->notifyRequirementCreated($requirement);
        } catch (Throwable $exception) {
            Log::warning('Requirement created but notifications failed.', [
                'requirement_id' => (string) $requirement->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Requirement created successfully.',
            'data' => new RequirementDetailResource($requirement),
            'meta' => null,
        ], 201);
    }

    public function myIndex(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->query('per_page', 20), 100));

        $paginated = Requirement::query()
            ->with('user')
            ->withCount('interests')
            ->where('user_id', $request->user()->id)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'My requirements fetched successfully.',
            'data' => RequirementDetailResource::collection($paginated->items()),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function show(Request $request, Requirement $requirement): JsonResponse
    {
        $user = $request->user();

        if ((string) $requirement->user_id !== (string) $user->id && (string) $requirement->status !== 'open') {
            return response()->json([
                'status' => false,
                'message' => 'Requirement not found.',
                'data' => null,
                'meta' => null,
            ], 404);
        }

        $requirement->load(['user', 'interests.user'])->loadCount('interests');

        return response()->json([
            'status' => true,
            'message' => 'Requirement fetched successfully.',
            'data' => new RequirementDetailResource($requirement),
            'meta' => null,
        ]);
    }

    public function close(CloseRequirementRequest $request, Requirement $requirement): JsonResponse
    {
        if ((string) $request->user()->id !== (string) $requirement->user_id) {
            return response()->json([
                'status' => false,
                'message' => 'Only creator can close/complete this requirement.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        $requirement->update([
            'status' => $request->string('status')->toString(),
        ]);

        $requirement->loadCount('interests');

        return response()->json([
            'status' => true,
            'message' => 'Requirement updated successfully.',
            'data' => new RequirementDetailResource($requirement),
            'meta' => null,
        ]);
    }
}

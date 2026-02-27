<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Requirements\CloseRequirementRequest;
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

    public function store(Request $request): JsonResponse
    {
        Log::info('Create requirement request received', [
            'user_id' => auth()->id(),
            'payload' => $request->all(),
        ]);

        if (! auth()->id()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated',
                'data' => null,
                'meta' => null,
            ], 401);
        }

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'media' => ['nullable', 'array'],
            'media.*.type' => ['nullable', 'string'],
            'media.*.file_id' => ['nullable', 'string'],
            'region_filter' => ['nullable', 'array'],
            'category_filter' => ['nullable', 'array'],
        ]);

        try {
            $requirement = Requirement::create([
                'user_id' => auth()->id(),
                'subject' => $validated['subject'],
                'description' => $validated['description'] ?? null,
                'media' => $validated['media'] ?? [],
                'region_filter' => $validated['region_filter'] ?? [],
                'category_filter' => $validated['category_filter'] ?? [],
                'status' => 'open',
            ]);

            try {
                $this->requirementNotificationService->notifyRequirementCreated($requirement->load('user'));
            } catch (Throwable $exception) {
                Log::warning('Requirement created but notification failed.', [
                    'requirement_id' => (string) $requirement->id,
                    'error' => $exception->getMessage(),
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Requirement created',
                'data' => $requirement,
                'meta' => null,
            ], 201);
        } catch (Throwable $e) {
            Log::error('Requirement create failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Server error',
                'data' => null,
                'meta' => null,
            ], 500);
        }
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

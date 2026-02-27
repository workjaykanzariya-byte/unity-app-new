<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\ActivityCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Requirements\CloseRequirementRequest;
use App\Http\Requests\Requirements\StoreRequirementRequest;
use App\Http\Resources\Requirement\RequirementDetailResource;
use App\Models\Post;
use App\Models\Requirement;
use App\Services\Requirements\RequirementNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RequirementController extends Controller
{
    public function __construct(private readonly RequirementNotificationService $requirementNotificationService)
    {
    }

    public function store(StoreRequirementRequest $request): JsonResponse
    {
        $user = $request->user();

        $requirement = Requirement::create([
            'user_id' => $user->id,
            'subject' => $request->input('subject'),
            'description' => $request->input('description'),
            'media' => $request->input('media', []),
            'region_filter' => $request->input('region_filter', []),
            'category_filter' => $request->input('category_filter', []),
            'status' => 'open',
        ]);

        $timelinePost = Post::create([
            'user_id' => $user->id,
            'circle_id' => null,
            'content_text' => trim($requirement->subject . ' ' . ($requirement->description ?? '')),
            'media' => collect($requirement->media ?? [])->map(function ($item) {
                if (is_string($item)) {
                    return ['id' => null, 'type' => 'unknown', 'url' => $item];
                }

                $id = data_get($item, 'id');

                return [
                    'id' => $id,
                    'type' => data_get($item, 'type', 'image'),
                    'url' => data_get($item, 'url') ?: ($id ? url('/api/v1/files/' . $id) : null),
                ];
            })->values()->all(),
            'tags' => [],
            'visibility' => 'public',
            'moderation_status' => 'pending',
            'sponsored' => false,
            'is_deleted' => false,
        ]);

        $requirement->update(['timeline_post_id' => $timelinePost->id]);
        $requirement->load('user');

        event(new ActivityCreated('Requirement', $requirement, (string) $user->id, null));
        $this->requirementNotificationService->notifyRequirementCreated($requirement);

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
            ->with('user:id,first_name,last_name,display_name,company_name,city,profile_photo_file_id')
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

        if ((string) $requirement->user_id !== (string) $user->id && $requirement->status !== 'open') {
            return response()->json([
                'status' => false,
                'message' => 'Requirement not found.',
                'data' => null,
                'meta' => null,
            ], 404);
        }

        $requirement->load([
            'user:id,first_name,last_name,display_name,company_name,city,profile_photo_file_id',
            'interests.user:id,first_name,last_name,display_name,company_name,city,profile_photo_file_id',
        ])->loadCount('interests');

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
                'message' => 'Only creator can close this requirement.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        $closeType = $request->input('close_type');

        $requirement->update([
            'status' => $closeType,
            'closed_at' => $closeType === 'closed' ? now() : $requirement->closed_at,
            'completed_at' => $closeType === 'completed' ? now() : $requirement->completed_at,
        ]);

        if ($requirement->timeline_post_id) {
            Post::query()->where('id', $requirement->timeline_post_id)->update(['is_deleted' => true]);
        }

        $requirement->loadCount('interests');

        return response()->json([
            'status' => true,
            'message' => 'Requirement ' . $closeType . ' successfully.',
            'data' => new RequirementDetailResource($requirement),
            'meta' => null,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\Activities;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Activities\StoreRequirementRequest;
use App\Http\Resources\RequirementResource;
use App\Models\Requirement;
use App\Services\CoinsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class RequirementController extends BaseApiController
{
    public function __construct(private CoinsService $coinsService)
    {
    }

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

            $coins = $this->coinsService->credit(
                userId: $user->id,
                activityId: $requirement->id,
                reference: 'requirements',
                coins: 5,
            );

            $payload = [
                'requirement' => new RequirementResource($requirement),
                'coins_earned' => $coins['coins_earned'],
                'total_coins' => $coins['total_coins'],
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

            if (config('app.env') !== 'production') {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ], 500);
            }

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

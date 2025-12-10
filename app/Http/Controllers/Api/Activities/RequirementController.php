<?php

namespace App\Http\Controllers\Api\Activities;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Activities\StoreRequirementRequest;
use App\Http\Resources\RequirementResource;
use App\Models\File;
use App\Models\Requirement;
use App\Services\Coins\CoinsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class RequirementController extends BaseApiController
{
    public function store(StoreRequirementRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();
        $mediaItems = $this->buildMediaItems($data);

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
                'media' => $mediaItems ?: [],
                'region_filter' => $regionFilter,
                'category_filter' => $categoryFilter,
                'category' => $data['category'],
                'region_label' => $data['region_label'],
                'city_name' => $data['city_name'],
                'budget' => $data['budget'] ?? null,
                'timeline' => $data['timeline'] ?? null,
                'tags' => $data['tags'] ?? [],
                'visibility' => $data['visibility'],
                'status' => $data['status'] ?? 'open',
            ]);

            $coinsLedger = app(CoinsService::class)->rewardForActivity(
                $user,
                'requirement',
                null,
                'Activity: requirement',
                $user->id
            );

            if ($coinsLedger) {
                $requirement->setAttribute('coins', [
                    'earned' => $coinsLedger->amount,
                    'balance_after' => $coinsLedger->balance_after,
                ]);
            }

            return $this->success(
                new RequirementResource($requirement),
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

    private function buildMediaItems(array $data): array
    {
        $mediaItems = [];

        if (! empty($data['media'])) {
            $fileIds = collect($data['media'])->pluck('id')->all();

            $files = File::whereIn('id', $fileIds)->get()->keyBy('id');

            foreach ($data['media'] as $item) {
                $file = $files->get($item['id']);
                if (! $file) {
                    continue;
                }

                $mediaItems[] = [
                    'id' => $file->id,
                    'type' => $item['type'],
                    'url' => $file->url,
                ];
            }
        }

        return $mediaItems;
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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\File;
use App\Models\Requirement;
use App\Services\Coins\CoinsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class RequirementController extends BaseApiController
{
    public function index(Request $request)
    {
        $authUser = $request->user();
        $status = $request->input('status');

        $query = Requirement::query()
            ->where('user_id', $authUser->id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at');

        if ($status) {
            $query->where('status', $status);
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        $paginator = $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $items = collect($paginator->items())
            ->map(fn (Requirement $requirement) => $this->transformRequirement($requirement))
            ->values();

        return $this->success([
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $authUser = $request->user();
        $data = $this->validateRequirement($request);

        $mediaItems = $this->buildMediaItems($data);

        try {
            $requirement = Requirement::create([
                'user_id' => $authUser->id,
                'subject' => $data['subject'],
                'description' => $data['description'],
                'region_filter' => [
                    'city_name' => $data['city_name'],
                    'region_label' => $data['region_label'],
                ],
                'category_filter' => [
                    'category' => $data['category'],
                ],
                'category' => $data['category'],
                'region_label' => $data['region_label'],
                'city_name' => $data['city_name'],
                'budget' => $data['budget'] ?? null,
                'timeline' => $data['timeline'] ?? null,
                'tags' => $data['tags'] ?? [],
                'visibility' => $data['visibility'],
                'media' => $mediaItems ?: [],
                'status' => $data['status'] ?? 'open',
                'is_deleted' => false,
            ]);

            $coinsLedger = app(CoinsService::class)->rewardForActivity(
                $authUser,
                'requirement',
                null,
                'Activity: requirement',
                $authUser->id
            );

            if ($coinsLedger) {
                $requirement->setAttribute('coins', [
                    'earned' => $coinsLedger->amount,
                    'balance_after' => $coinsLedger->balance_after,
                ]);
            }

            return $this->success(
                $this->transformRequirement($requirement),
                'Requirement created successfully',
                201
            );
        } catch (Throwable $e) {
            Log::error('Failed to create requirement', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error('Something went wrong', 500);
        }
    }

    public function show(Request $request, string $id)
    {
        $authUser = $request->user();

        $requirement = Requirement::where('id', $id)
            ->where('user_id', $authUser->id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->first();

        if (! $requirement) {
            return $this->error('Requirement not found', 404);
        }

        return $this->success($this->transformRequirement($requirement));
    }

    public function update(Request $request, string $id)
    {
        $authUser = $request->user();
        $data = $this->validateRequirement($request);

        $requirement = Requirement::where('id', $id)
            ->where('user_id', $authUser->id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->first();

        if (! $requirement) {
            return $this->error('Requirement not found', 404);
        }

        $mediaItems = $this->buildMediaItems($data);

        try {
            $requirement->update([
                'subject' => $data['subject'],
                'description' => $data['description'],
                'region_filter' => [
                    'city_name' => $data['city_name'],
                    'region_label' => $data['region_label'],
                ],
                'category_filter' => [
                    'category' => $data['category'],
                ],
                'category' => $data['category'],
                'region_label' => $data['region_label'],
                'city_name' => $data['city_name'],
                'budget' => $data['budget'] ?? null,
                'timeline' => $data['timeline'] ?? null,
                'tags' => $data['tags'] ?? [],
                'visibility' => $data['visibility'],
                'media' => $mediaItems ?: [],
                'status' => $data['status'] ?? $requirement->status,
            ]);

            return $this->success($this->transformRequirement($requirement->refresh()));
        } catch (Throwable $e) {
            Log::error('Failed to update requirement', [
                'requirement_id' => $requirement->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error('Something went wrong', 500);
        }
    }

    private function validateRequirement(Request $request): array
    {
        $rules = [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'region_label' => ['required', 'string', 'max:100'],
            'city_name' => ['required', 'string', 'max:100'],
            'category' => ['required', 'string', 'max:100'],
            'budget' => ['nullable', 'numeric'],
            'timeline' => ['nullable', 'string', 'max:100'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
            'visibility' => ['required', 'in:public,connections,private'],
            'status' => ['nullable', 'in:open,in_progress,closed'],
            'media' => ['nullable', 'array'],
            'media.*.id' => ['required_with:media', 'uuid', 'exists:files,id'],
            'media.*.type' => ['required_with:media', 'string', 'max:50'],
        ];

        return $request->validate($rules);
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

    private function transformRequirement(Requirement $requirement): array
    {
        return [
            'id' => $requirement->id,
            'user_id' => $requirement->user_id,
            'subject' => $requirement->subject,
            'description' => $requirement->description,
            'region_filter' => $requirement->region_filter,
            'category_filter' => $requirement->category_filter,
            'region_label' => $requirement->region_label ?? ($requirement->region_filter['region_label'] ?? null),
            'city_name' => $requirement->city_name ?? ($requirement->region_filter['city_name'] ?? null),
            'category' => $requirement->category ?? ($requirement->category_filter['category'] ?? null),
            'budget' => $requirement->budget,
            'timeline' => $requirement->timeline,
            'tags' => $requirement->tags ?? [],
            'visibility' => $requirement->visibility,
            'media' => $requirement->media ?? [],
            'status' => $requirement->status,
            'created_at' => $requirement->created_at,
            'updated_at' => $requirement->updated_at,
        ];
    }
}

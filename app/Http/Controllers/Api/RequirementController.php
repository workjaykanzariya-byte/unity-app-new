<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Activity\StoreRequirementRequest;
use App\Models\Requirement;
use App\Services\Coins\CoinsService;
use Illuminate\Http\Request;
use Throwable;

class RequirementController extends BaseApiController
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);
        $page = (int) $request->input('page', 1);

        $query = Requirement::query()
            ->where('is_deleted', false)
            ->whereNull('deleted_at');

        $paginator = $query
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginator->items())
            ->map(fn (Requirement $requirement) => $this->formatRequirement($requirement))
            ->all();

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

    public function store(StoreRequirementRequest $request)
    {
        $authUser = $request->user();

        $media = null;
        if ($request->filled('media_id')) {
            $media = [[
                'id' => $request->input('media_id'),
                'type' => 'image',
            ]];
        }

        try {
            $requirement = Requirement::create([
                'user_id' => $authUser->id,
                'subject' => $request->input('subject'),
                'description' => $request->input('description'),
                'media' => $media,
                'region_label' => $request->input('region_label'),
                'city_name' => $request->input('city_name'),
                'category' => $request->input('category'),
                'status' => $request->input('status', 'open') ?: 'open',
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
                $this->formatRequirement($requirement),
                'Requirement created successfully',
                201
            );
        } catch (Throwable $e) {
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

        return $this->success($this->formatRequirement($requirement));
    }

    protected function addUrlsToMedia(?array $media): array
    {
        if (empty($media)) {
            return [];
        }

        return collect($media)->map(function ($item) {
            $id = $item['id'] ?? null;
            $type = $item['type'] ?? 'image';

            return [
                'id' => $id,
                'type' => $type,
                'url' => $id ? url('/api/v1/files/' . $id) : null,
            ];
        })->all();
    }

    protected function formatRequirement(Requirement $requirement): array
    {
        $data = [
            'id' => $requirement->id,
            'user_id' => $requirement->user_id,
            'subject' => $requirement->subject,
            'description' => $requirement->description,
            'media' => $this->addUrlsToMedia($requirement->media),
            'region_filter' => $requirement->region_filter,
            'category_filter' => $requirement->category_filter ?? null,
            'region_label' => $requirement->region_label ?? ($requirement->region_filter['region_label'] ?? null),
            'city_name' => $requirement->city_name ?? ($requirement->region_filter['city_name'] ?? null),
            'category' => $requirement->category ?? ($requirement->category_filter['category'] ?? null),
            'status' => $requirement->status,
            'created_at' => $requirement->created_at,
            'updated_at' => $requirement->updated_at,
        ];

        if ($requirement->getAttribute('coins')) {
            $data['coins'] = $requirement->getAttribute('coins');
        }

        return $data;
    }
}

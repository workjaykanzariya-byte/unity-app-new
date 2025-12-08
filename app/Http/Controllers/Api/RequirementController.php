<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Activity\StoreRequirementRequest;
use App\Models\Requirement;
use App\Traits\HandlesCoins;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class RequirementController extends BaseApiController
{
    use HandlesCoins;

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

        return $this->success([
            'items' => $paginator->items(),
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

            $coins = $this->creditCoinsForActivity(
                $authUser,
                $requirement->id,
                'requirements',
                3000
            );

            $payload = $requirement->toArray();
            $payload['coins_earned'] = $coins['coins_earned'];
            $payload['total_coins'] = $coins['total_coins'];

            return $this->success($payload, 'Requirement created successfully', 201);
        } catch (Throwable $e) {
            Log::error('Requirement store error', [
                'user_id' => $authUser->id,
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

        return $this->success($requirement);
    }
}

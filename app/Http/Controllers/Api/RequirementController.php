<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Requirement\StoreRequirementRequest;
use App\Http\Requests\Requirement\UpdateRequirementRequest;
use App\Http\Resources\RequirementResource;
use App\Models\Requirement;
use Illuminate\Http\Request;

class RequirementController extends BaseApiController
{
    public function store(StoreRequirementRequest $request)
    {
        $authUser = $request->user();
        $data = $request->validated();

        $requirement = new Requirement();
        $requirement->user_id = $authUser->id;
        $requirement->subject = $data['subject'];
        $requirement->description = $data['description'] ?? null;
        $requirement->media = $data['media'] ?? null;
        $requirement->region_filter = $data['region_filter'] ?? null;
        $requirement->category_filter = $data['category_filter'] ?? null;
        $requirement->status = 'open';
        $requirement->save();

        $requirement->refresh();
        $requirement->load('user');

        return $this->success(new RequirementResource($requirement), 'Requirement created successfully', 201);
    }

    public function index(Request $request)
    {
        $authUser = $request->user();

        $query = Requirement::query()
            ->with('user')
            ->whereNull('deleted_at');

        $status = $request->input('status', 'open');
        if ($status) {
            $query->where('status', $status);
        }

        if ($request->boolean('my', false)) {
            $query->where('user_id', $authUser->id);
        } elseif ($ownerId = $request->input('owner_id')) {
            $query->where('user_id', $ownerId);
        }

        if ($search = trim((string) $request->input('q', ''))) {
            $searchLike = '%' . $search . '%';
            $query->where(function ($q) use ($searchLike) {
                $q->where('subject', 'ILIKE', $searchLike)
                    ->orWhere('description', 'ILIKE', $searchLike);
            });
        }

        if ($region = $request->input('region')) {
            $query->whereJsonContains('region_filter', $region);
        }

        if ($category = $request->input('category')) {
            $query->whereJsonContains('category_filter', $category);
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        $paginator = $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $data = [
            'items' => RequirementResource::collection($paginator),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];

        return $this->success($data);
    }

    public function show(Request $request, string $id)
    {
        $requirement = Requirement::with('user')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (! $requirement) {
            return $this->error('Requirement not found', 404);
        }

        return $this->success(new RequirementResource($requirement));
    }

    public function update(UpdateRequirementRequest $request, string $id)
    {
        $authUser = $request->user();
        $data = $request->validated();

        $requirement = Requirement::where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (! $requirement) {
            return $this->error('Requirement not found', 404);
        }

        if ($requirement->user_id !== $authUser->id) {
            // TODO: allow admins to edit any requirement via gate/middleware
            return $this->error('You are not allowed to update this requirement', 403);
        }

        $requirement->fill($data);
        $requirement->save();

        $requirement->load('user');

        return $this->success(new RequirementResource($requirement), 'Requirement updated successfully');
    }
}

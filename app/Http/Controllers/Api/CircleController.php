<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Circle\CircleJoinRequest;
use App\Http\Requests\Circle\StoreCircleRequest;
use App\Http\Requests\Circle\UpdateCircleRequest;
use App\Http\Requests\Circle\UpdateCircleMemberRequest;
use App\Http\Resources\CircleMemberResource;
use App\Http\Resources\CircleResource;
use App\Models\Circle;
use App\Models\CircleMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CircleController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Circle::query()
            ->with(['city', 'founder']);

        $status = $request->input('status', 'active');
        if ($status) {
            $query->where('status', $status);
        }

        if ($cityId = $request->input('city_id')) {
            $query->where('city_id', $cityId);
        }

        if ($search = trim((string) $request->input('q', ''))) {
            $query->where('name', 'ILIKE', '%' . $search . '%');
        }

        $tags = $request->input('industry_tags') ?? $request->input('tag');
        if ($tags) {
            if (is_string($tags)) {
                $tags = array_filter(array_map('trim', explode(',', $tags)));
            }
            if (is_array($tags) && count($tags) > 0) {
                $query->where(function ($q) use ($tags) {
                    foreach ($tags as $tag) {
                        $q->orWhereJsonContains('industry_tags', $tag);
                    }
                });
            }
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        $paginator = $query->orderBy('name')->paginate($perPage);

        $data = [
            'items' => CircleResource::collection($paginator),
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
        $circle = Circle::with(['city', 'founder'])->find($id);

        if (! $circle) {
            return $this->error('Circle not found', 404);
        }

        $membersCount = $circle->members()
            ->whereNull('deleted_at')
            ->count();

        $approvedMembersCount = $circle->members()
            ->whereNull('deleted_at')
            ->where('status', 'approved')
            ->count();

        $resource = new CircleResource($circle);
        $data = $resource->toArray($request);
        $data['members_count'] = $membersCount;
        $data['approved_members_count'] = $approvedMembersCount;

        return $this->success($data);
    }

    public function store(StoreCircleRequest $request)
    {
        $authUser = $request->user();
        $data = $request->validated();

        return DB::transaction(function () use ($data, $authUser) {
            $circle = new Circle();
            $circle->fill($data);
            $circle->founder_user_id = $authUser->id;
            $circle->save();

            CircleMember::create([
                'circle_id' => $circle->id,
                'user_id' => $authUser->id,
                'role' => 'founder',
                'status' => 'approved',
                'joined_at' => now(),
            ]);

            $circle->load(['city', 'founder']);

            return $this->success(new CircleResource($circle), 'Circle created successfully', 201);
        });
    }

    public function join(CircleJoinRequest $request, string $id)
    {
        $authUser = $request->user();

        $circle = Circle::find($id);
        if (! $circle) {
            return $this->error('Circle not found', 404);
        }

        $existing = CircleMember::where('circle_id', $circle->id)
            ->where('user_id', $authUser->id)
            ->first();

        if ($existing) {
            if ($existing->status === 'approved') {
                return $this->error('You are already a member of this circle', 422);
            }
            if ($existing->status === 'pending') {
                return $this->error('Your membership request is already pending', 422);
            }

            $existing->status = 'pending';
            $existing->joined_at = null;
            $existing->save();

            return $this->success(new CircleMemberResource($existing->load('user')), 'Membership request submitted again');
        }

        $member = CircleMember::create([
            'circle_id' => $circle->id,
            'user_id' => $authUser->id,
            'role' => 'member',
            'status' => 'pending',
        ]);

        $member->load('user');

        return $this->success(new CircleMemberResource($member), 'Membership request submitted', 201);
    }

    public function myCircles(Request $request)
    {
        $authUser = $request->user();

        $memberships = CircleMember::with(['circle.city', 'circle.founder'])
            ->where('user_id', $authUser->id)
            ->where('status', 'approved')
            ->whereNull('deleted_at')
            ->get();

        $circles = $memberships->pluck('circle')->filter();

        return $this->success(CircleResource::collection($circles->unique('id')->values()));
    }

    public function members(Request $request, string $id)
    {
        $circle = Circle::find($id);
        if (! $circle) {
            return $this->error('Circle not found', 404);
        }

        $status = $request->input('status', 'approved');

        $membersQuery = CircleMember::with('user')
            ->where('circle_id', $circle->id)
            ->whereNull('deleted_at');

        if ($status) {
            $membersQuery->where('status', $status);
        }

        $members = $membersQuery->orderBy('role')->orderBy('joined_at')->get();

        return $this->success(CircleMemberResource::collection($members));
    }

    public function update(UpdateCircleRequest $request, string $id)
    {
        $authUser = $request->user();

        $circle = Circle::with(['city', 'founder'])->find($id);

        if (! $circle) {
            return $this->error('Circle not found', 404);
        }

        if ($circle->founder_user_id !== $authUser->id) {
            return $this->error('You are not allowed to update this circle', 403);
        }

        $circle->fill($request->validated());
        $circle->save();

        $circle->load(['city', 'founder']);

        return $this->success(new CircleResource($circle), 'Circle updated successfully');
    }

    public function updateMember(UpdateCircleMemberRequest $request, string $circleId, string $memberId)
    {
        $authUser = $request->user();

        $circle = Circle::find($circleId);
        if (! $circle) {
            return $this->error('Circle not found', 404);
        }

        $authMembership = CircleMember::where('circle_id', $circle->id)
            ->where('user_id', $authUser->id)
            ->whereNull('deleted_at')
            ->first();

        if (! $authMembership) {
            return $this->error('You are not a member of this circle', 403);
        }

        $adminRoles = ['founder', 'director', 'chair', 'vice_chair', 'secretary'];
        if (! in_array($authMembership->role, $adminRoles, true)) {
            return $this->error('You are not allowed to manage circle members', 403);
        }

        $member = CircleMember::with('user')
            ->where('circle_id', $circle->id)
            ->where('id', $memberId)
            ->whereNull('deleted_at')
            ->first();

        if (! $member) {
            return $this->error('Circle member not found', 404);
        }

        $data = $request->validated();

        if (isset($data['status']) && $data['status'] === 'approved' && ! $member->joined_at) {
            $member->joined_at = now();
        }

        $member->fill($data);
        $member->save();

        return $this->success(new CircleMemberResource($member), 'Circle member updated');
    }
}

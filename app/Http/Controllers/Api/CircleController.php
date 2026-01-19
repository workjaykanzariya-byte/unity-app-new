<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Circle\StoreCircleRequest;
use App\Http\Requests\Circle\UpdateCircleMemberRequest;
use App\Http\Resources\CircleMemberResource;
use App\Http\Resources\CircleResource;
use App\Models\Circle;
use App\Models\CircleMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CircleController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Circle::query()
            ->with([
                'founder:id,first_name,last_name,display_name,profile_photo_url',
                'city:id,name',
            ])
            ->withCount([
                'members as members_count' => function ($q) {
                    $q->where('status', 'approved');
                },
            ]);

        if ($search = trim((string) ($request->input('search') ?? $request->input('q', '')))) {
            $query->where(function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where('name', 'ILIKE', $like)
                    ->orWhere('description', 'ILIKE', $like)
                    ->orWhere('purpose', 'ILIKE', $like);
            });
        }

        if ($cityId = $request->input('city_id')) {
            $query->where('city_id', $cityId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $paginator = $query
            ->orderBy('name')
            ->paginate(20);

        $circleIds = collect($paginator->items())->pluck('id');
        $memberships = CircleMember::whereIn('circle_id', $circleIds)
            ->where('user_id', $request->user()->id)
            ->get()
            ->keyBy('circle_id');

        foreach ($paginator->items() as $circle) {
            $circle->setRelation('currentMember', $memberships->get($circle->id));
        }

        $data = [
            'items' => CircleResource::collection($paginator->items()),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];

        return $this->success($data);
    }

    public function show(string $id)
    {
        $circle = Circle::with(['city', 'founder'])
            ->withCount([
                'members as members_count' => function ($q) {
                    $q->where('status', 'approved');
                },
            ])
            ->find($id);

        if (! $circle) {
            return $this->error('Circle not found', 404);
        }

        $circle->setRelation(
            'currentMember',
            $circle->members()
                ->where('user_id', auth()->id())
                ->first()
        );

        return $this->success(new CircleResource($circle));
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

    public function join(Request $request, string $circleId)
    {
        $user = $request->user();

        if (! Str::isUuid($circleId)) {
            return $this->error('Invalid circle id format', 422);
        }

        $circle = Circle::find($circleId);
        if (! $circle) {
            return $this->error('Circle not found', 404);
        }

        if ($circle->founder_user_id === $user->id) {
            return $this->success(null, 'You are the founder of this circle');
        }

        $existing = CircleMember::where('circle_id', $circle->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return $this->success(null, 'You have already requested to join or are already a member');
        }

        $member = CircleMember::create([
            'circle_id' => $circle->id,
            'user_id' => $user->id,
            'role' => 'member',
            'status' => 'pending',
            'substitute_count' => 0,
        ]);

        return $this->success($member, 'Join request submitted successfully', 201);
    }

    public function myCircles(Request $request)
    {
        $user = $request->user();

        $circleIds = CircleMember::where('user_id', $user->id)
            ->where('status', '!=', 'rejected')
            ->pluck('circle_id');

        $paginator = Circle::whereIn('id', $circleIds)
            ->with([
                'founder:id,first_name,last_name,display_name,profile_photo_url',
                'city:id,name',
            ])
            ->withCount([
                'members as members_count' => function ($q) {
                    $q->where('status', 'approved');
                },
            ])
            ->orderBy('name')
            ->paginate(20);

        $memberships = CircleMember::whereIn('circle_id', $paginator->pluck('id'))
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('circle_id');

        foreach ($paginator->items() as $circle) {
            $circle->setRelation('currentMember', $memberships->get($circle->id));
        }

        $data = [
            'items' => CircleResource::collection($paginator->items()),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];

        return $this->success($data);
    }

    public function members(Request $request, string $id)
    {
        $circle = Circle::find($id);
        if (! $circle) {
            return $this->error('Circle not found', 404);
        }

        $status = $request->input('status', 'approved');

        $membersQuery = CircleMember::with(['user', 'roleRef'])
            ->where('circle_id', $circle->id)
            ->whereNull('deleted_at');

        if ($status) {
            $membersQuery->where('status', $status);
        }

        $members = $membersQuery->orderBy('role')->orderBy('joined_at')->get();

        return $this->success(CircleMemberResource::collection($members));
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

        $member = CircleMember::with(['user', 'roleRef'])
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

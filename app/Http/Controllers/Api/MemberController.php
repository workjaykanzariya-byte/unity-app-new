<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ConnectionResource;
use App\Http\Resources\PublicUserResource;
use App\Http\Resources\UserResource;
use App\Models\Connection;
use App\Models\User;
use Illuminate\Http\Request;

class MemberController extends BaseApiController
{
    public function index(Request $request)
    {
        $authUser = $request->user();

        $query = User::query()
            ->with('city')
            ->where('id', '!=', $authUser->id);

        if (! $request->boolean('include_suspended', false)) {
            $query->where('membership_status', '!=', 'suspended');
        }

        if ($search = trim((string) $request->input('q', ''))) {
            $query->whereRaw(
                "search_vector @@ plainto_tsquery('simple', unaccent(?))",
                [$search]
            );
        }

        if ($cityId = $request->input('city_id')) {
            $query->where('city_id', $cityId);
        }

        if ($status = $request->input('membership_status')) {
            $query->where('membership_status', $status);
        }

        $tags = $request->input('industry_tags');
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
        if ($perPage < 1) {
            $perPage = 20;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $paginator = $query->paginate($perPage);

        $data = [
            'items' => UserResource::collection($paginator),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];

        return $this->success($data);
    }

    public function names()
    {
        $names = User::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        return $this->success($names);
    }

    public function show(Request $request, string $id)
    {
        $user = User::with('city')->find($id);

        if (! $user) {
            return $this->error('Member not found', 404);
        }

        return $this->success(new UserResource($user));
    }

    public function publicProfileBySlug(Request $request, string $slug)
    {
        $user = User::with('city')
            ->where('public_profile_slug', $slug)
            ->first();

        if (! $user) {
            return $this->error('Public profile not found', 404);
        }

        return $this->success(new PublicUserResource($user));
    }

    public function sendConnectionRequest(Request $request, string $id)
    {
        $authUser = $request->user();

        if ($authUser->id === $id) {
            return $this->error('You cannot connect to yourself', 422);
        }

        $target = User::find($id);
        if (! $target) {
            return $this->error('Member not found', 404);
        }

        $existing = Connection::where(function ($q) use ($authUser, $target) {
                $q->where('requester_id', $authUser->id)
                    ->where('addressee_id', $target->id);
            })
            ->orWhere(function ($q) use ($authUser, $target) {
                $q->where('requester_id', $target->id)
                    ->where('addressee_id', $authUser->id);
            })
            ->first();

        if ($existing) {
            if ($existing->is_approved) {
                return $this->error('You are already connected with this member', 422);
            }

            return $this->error('A connection request already exists', 422);
        }

        $connection = Connection::create([
            'requester_id' => $authUser->id,
            'addressee_id' => $target->id,
            'is_approved' => false,
        ]);

        $connection->load(['requester', 'addressee']);

        return $this->success(new ConnectionResource($connection), 'Connection request sent', 201);
    }

    public function acceptConnection(Request $request, string $id)
    {
        $authUser = $request->user();

        $connection = Connection::where('requester_id', $id)
            ->where('addressee_id', $authUser->id)
            ->where('is_approved', false)
            ->first();

        if (! $connection) {
            return $this->error('Connection request not found', 404);
        }

        $connection->is_approved = true;
        $connection->approved_at = now();
        $connection->save();

        $connection->load(['requester', 'addressee']);

        return $this->success(new ConnectionResource($connection), 'Connection request accepted');
    }

    public function deleteConnection(Request $request, string $id)
    {
        $authUser = $request->user();

        $connection = Connection::where(function ($q) use ($authUser, $id) {
                $q->where('requester_id', $authUser->id)
                    ->where('addressee_id', $id);
            })
            ->orWhere(function ($q) use ($authUser, $id) {
                $q->where('requester_id', $id)
                    ->where('addressee_id', $authUser->id);
            })
            ->first();

        if (! $connection) {
            return $this->error('Connection not found', 404);
        }

        $connection->delete();

        return $this->success(null, 'Connection removed');
    }

    public function myConnections(Request $request)
    {
        $authUser = $request->user();

        $connections = Connection::with(['requester', 'addressee'])
            ->where('is_approved', true)
            ->where(function ($q) use ($authUser) {
                $q->where('requester_id', $authUser->id)
                    ->orWhere('addressee_id', $authUser->id);
            })
            ->orderBy('approved_at', 'desc')
            ->get();

        return $this->success(ConnectionResource::collection($connections));
    }

    public function myConnectionRequests(Request $request)
    {
        $authUser = $request->user();

        $connections = Connection::with(['requester', 'addressee'])
            ->where('addressee_id', $authUser->id)
            ->where('is_approved', false)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success(ConnectionResource::collection($connections));
    }
}

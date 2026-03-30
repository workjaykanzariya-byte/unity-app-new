<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PublicUserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PublicUserController extends BaseApiController
{
    public function index(Request $request)
    {
        $userTable = (new User)->getTable();
        $availableColumns = $this->resolveAvailableColumns($userTable);
        $perPage = max(1, min((int) $request->integer('per_page', 20), 100));
        $hasCircleMembersTable = Schema::hasTable('circle_members');

        $query = User::query()->select($availableColumns);

        if (in_array('deleted_at', $availableColumns, true)) {
            $query->whereNull('deleted_at');
        }

        if (in_array('status', $availableColumns, true)) {
            $query->where(function ($statusQuery) {
                $statusQuery->whereNull('status')->orWhere('status', 'active');
            });
        }

        if ($search = trim((string) $request->query('search', ''))) {
            $searchableColumns = array_values(array_intersect([
                'display_name',
                'first_name',
                'last_name',
                'email',
                'phone',
                'company_name',
            ], $availableColumns));

            $query->where(function ($searchQuery) use ($searchableColumns, $search) {
                if ($searchableColumns !== []) {
                    foreach ($searchableColumns as $column) {
                        $searchQuery->orWhere($column, 'ILIKE', '%' . $search . '%');
                    }
                }

                $searchQuery->orWhereHas('city', function ($cityQuery) use ($search) {
                    $cityQuery->where('name', 'ILIKE', '%' . $search . '%');
                });
            });
        }

        if (
            $request->filled('membership_status')
            && in_array('membership_status', $availableColumns, true)
        ) {
            $query->where('membership_status', (string) $request->query('membership_status'));
        }

        if (
            $request->filled('city_id')
            && in_array('city_id', $availableColumns, true)
        ) {
            $query->where('city_id', $request->query('city_id'));
        }

        if (
            $request->filled('status')
            && in_array('status', $availableColumns, true)
        ) {
            $query->where('status', (string) $request->query('status'));
        }

        if ($request->filled('circle_id') && $hasCircleMembersTable) {
            $circleId = (string) $request->query('circle_id');
            $query->whereHas('circleMembers', function ($circleMembersQuery) use ($circleId) {
                $circleMembersQuery
                    ->where('circle_id', $circleId)
                    ->where('status', $this->activeCircleMemberStatus())
                    ->whereNull('deleted_at')
                    ->whereNull('left_at');
            });
        }

        if (in_array('city_id', $availableColumns, true)) {
            $query->with('city:id,name');
        }

        if ($hasCircleMembersTable) {
            $query->with([
                'circleMembers' => function ($circleMembersQuery) {
                    $circleMembersQuery
                        ->select(['id', 'user_id', 'circle_id', 'status', 'joined_at', 'deleted_at', 'left_at'])
                        ->where('status', $this->activeCircleMemberStatus())
                        ->whereNull('deleted_at')
                        ->whereNull('left_at')
                        ->orderByDesc('joined_at')
                        ->with(['circle:id,name']);
                },
            ]);
        }

        if (in_array('created_at', $availableColumns, true)) {
            $query->orderByDesc('created_at');
        } else {
            $query->orderBy('id');
        }

        $users = $query->paginate($perPage)->appends($request->query());

        return $this->success([
            'items' => PublicUserResource::collection($users->getCollection()),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    private function resolveAvailableColumns(string $table): array
    {
        $candidates = [
            'id',
            'first_name',
            'last_name',
            'display_name',
            'email',
            'phone',
            'public_profile_slug',
            'company_name',
            'designation',
            'city_id',
            'country',
            'membership_status',
            'coins_balance',
            'last_login_at',
            'status',
            'profile_photo_url',
            'profile_photo_file_id',
            'created_at',
            'deleted_at',
        ];

        return array_values(array_filter(
            $candidates,
            static fn (string $column): bool => Schema::hasColumn($table, $column)
        ));
    }

    private function activeCircleMemberStatus(): string
    {
        return (string) config('circle.member_joined_status', 'approved');
    }
}

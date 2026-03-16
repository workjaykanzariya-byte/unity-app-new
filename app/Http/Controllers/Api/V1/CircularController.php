<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\CircularDetailResource;
use App\Http\Resources\CircularListResource;
use App\Models\Circular;
use App\Models\CircleMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CircularController extends BaseApiController
{
    public function index(Request $request)
    {
        /** @var User|null $user */
        $user = $request->user();

        // TEMP DEBUG FOR CIRCULAR API
        Log::debug('circulars_api_reached', [
            'path' => $request->path(),
            'full_url' => $request->fullUrl(),
            'request_user_id' => $user?->id,
            'request_user_class' => $user ? get_class($user) : null,
            'auth_id' => Auth::id(),
            'auth_user_id' => Auth::user()?->id,
            'auth_user_class' => Auth::user() ? get_class(Auth::user()) : null,
            'app_timezone' => config('app.timezone'),
            'now' => now()->toIso8601String(),
            'connection_name' => (new Circular())->getConnectionName() ?: config('database.default'),
            'database_name' => config('database.connections.' . ((new Circular())->getConnectionName() ?: config('database.default')) . '.database'),
        ]);

        if (! $user) {
            Log::warning('circulars_user_context', ['authenticated' => false]);

            return $this->error('Unauthenticated.', 401);
        }

        $now = now();
        $userCircleIds = $this->userCircleIds($user);

        // TEMP DEBUG FOR CIRCULAR API: required ordered count trace
        $totalCircularsInDb = Circular::query()->withTrashed()->count();
        $totalActiveCirculars = Circular::query()->where('status', 'ILIKE', 'active')->count();
        $totalActiveNotDeleted = Circular::query()->where('status', 'ILIKE', 'active')->whereNull('deleted_at')->count();
        $totalPublished = Circular::query()
            ->where('status', 'ILIKE', 'active')
            ->whereNull('deleted_at')
            ->where('publish_date', '<=', $now)
            ->count();
        $totalExpiryValid = Circular::query()
            ->where('status', 'ILIKE', 'active')
            ->whereNull('deleted_at')
            ->where('publish_date', '<=', $now)
            ->where(function (Builder $query): void {
                $query->whereNull('expiry_date')->orWhere('expiry_date', '>', now());
            })
            ->count();

        $baseVisibleQuery = Circular::query()->visibleNow();
        $baseVisibleCount = (clone $baseVisibleQuery)->count();

        $allMembersVisibleRows = (clone $baseVisibleQuery)
            ->where('audience_type', 'all_members')
            ->count();

        $circleMembersVisibleRows = (clone $baseVisibleQuery)
            ->where('audience_type', 'circle_members')
            ->count();

        $fempreneurVisibleRows = (clone $baseVisibleQuery)
            ->where('audience_type', 'fempreneur')
            ->count();

        $greenpreneurVisibleRows = (clone $baseVisibleQuery)
            ->where('audience_type', 'greenpreneur')
            ->count();

        // TEMP DEBUG FOR CIRCULAR API
        Log::debug('circulars_user_context', [
            'user_id' => $user->id,
            'user_city_id' => $user->city_id,
            'user_circle_ids' => $userCircleIds,
        ]);

        Log::debug('circulars_count_trace', [
            'circulars_total_count' => $totalCircularsInDb,
            'circulars_active_count' => $totalActiveCirculars,
            'circulars_active_not_deleted_count' => $totalActiveNotDeleted,
            'circulars_active_not_deleted_published_count' => $totalPublished,
            'circulars_active_not_deleted_published_expiry_valid_count' => $totalExpiryValid,
            'circulars_visible_count' => $baseVisibleCount,
            'circulars_audience_all_members_count' => $allMembersVisibleRows,
            'circulars_audience_circle_members_count' => $circleMembersVisibleRows,
            'circulars_audience_fempreneur_count' => $fempreneurVisibleRows,
            'circulars_audience_greenpreneur_count' => $greenpreneurVisibleRows,
        ]);

        $query = Circular::query()->visibleNow();

        $this->applyAudienceFilter($query, $user, $userCircleIds);

        $afterAudienceCount = (clone $query)->count();

        $query->orderedForFeed();

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $perPage = (int) min(max((int) $request->query('per_page', 20), 1), 100);

        $circulars = $query->paginate($perPage);

        $resourceCollection = CircularListResource::collection($circulars);
        $resolvedItems = $resourceCollection->resolve($request);
        $resolvedItemsCount = is_countable($resolvedItems) ? count($resolvedItems) : 0;

        // TEMP DEBUG FOR CIRCULAR API
        Log::debug('circulars_query_trace', [
            'circulars_after_audience_count' => $afterAudienceCount,
            'circulars_final_sql' => $sql,
            'circulars_final_bindings' => $bindings,
            'circulars_paginated_count' => $circulars->count(),
            'circulars_paginated_total' => $circulars->total(),
            'circulars_final_json_items_count' => $resolvedItemsCount,
        ]);

        return $this->success([
            'items' => $resourceCollection,
            'pagination' => [
                'current_page' => $circulars->currentPage(),
                'last_page' => $circulars->lastPage(),
                'per_page' => $circulars->perPage(),
                'total' => $circulars->total(),
            ],
        ]);
    }

    public function show(Request $request, string $id)
    {
        /** @var User|null $user */
        $user = $request->user();

        // TEMP DEBUG FOR CIRCULAR API
        Log::debug('circulars_detail_api_reached', [
            'path' => $request->path(),
            'full_url' => $request->fullUrl(),
            'requested_id' => $id,
            'request_user_id' => $user?->id,
            'request_user_class' => $user ? get_class($user) : null,
            'auth_id' => Auth::id(),
            'auth_user_id' => Auth::user()?->id,
            'auth_user_class' => Auth::user() ? get_class(Auth::user()) : null,
            'app_timezone' => config('app.timezone'),
            'now' => now()->toIso8601String(),
        ]);

        if (! $user) {
            return $this->error('Unauthenticated.', 401);
        }

        $userCircleIds = $this->userCircleIds($user);

        $query = Circular::query()->visibleNow()->where('id', $id);

        $this->applyAudienceFilter($query, $user, $userCircleIds);

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $circular = $query->first();

        // TEMP DEBUG FOR CIRCULAR API
        Log::debug('circulars_detail_query_trace', [
            'user_id' => $user->id,
            'requested_id' => $id,
            'user_circle_ids' => $userCircleIds,
            'sql' => $sql,
            'bindings' => $bindings,
            'found' => (bool) $circular,
        ]);

        if (! $circular) {
            return $this->error('Circular not found.', 404);
        }

        return $this->success(new CircularDetailResource($circular));
    }

    /**
     * Apply audience targeting only.
     *
     * Note: city_id and circle_id are audience constraints only for matching audience types,
     * they are not global hard filters for all_members circulars.
     */
    private function applyAudienceFilter(Builder $query, User $user, array $userCircleIds): void
    {
        $isFempreneur = $this->userHasSegment($user, 'fempreneur');
        $isGreenpreneur = $this->userHasSegment($user, 'greenpreneur');

        $query->where(function (Builder $audience) use ($user, $userCircleIds, $isFempreneur, $isGreenpreneur): void {
            // 1) all_members: visible to every authenticated app user.
            $audience->where(function (Builder $allMembers): void {
                $allMembers->where('audience_type', 'all_members');
            });

            // 2) circle_members: user must belong to circular's circle_id.
            $audience->orWhere(function (Builder $circleMembers) use ($userCircleIds): void {
                $circleMembers->where('audience_type', 'circle_members')
                    ->whereNotNull('circle_id')
                    ->when(
                        $userCircleIds !== [],
                        fn (Builder $q) => $q->whereIn('circle_id', $userCircleIds),
                        fn (Builder $q) => $q->whereRaw('1 = 0')
                    );
            });

            // 3) fempreneur: include only users matching fempreneur segment logic.
            if ($isFempreneur) {
                $audience->orWhere(function (Builder $fempreneur) use ($user): void {
                    $fempreneur->where('audience_type', 'fempreneur')
                        ->where(function (Builder $city) use ($user): void {
                            $city->whereNull('city_id')
                                ->orWhere('city_id', $user->city_id);
                        });
                });
            }

            // 4) greenpreneur: include only users matching greenpreneur segment logic.
            if ($isGreenpreneur) {
                $audience->orWhere(function (Builder $greenpreneur) use ($user): void {
                    $greenpreneur->where('audience_type', 'greenpreneur')
                        ->where(function (Builder $city) use ($user): void {
                            $city->whereNull('city_id')
                                ->orWhere('city_id', $user->city_id);
                        });
                });
            }
        });
    }

    private function userCircleIds(User $user): array
    {
        return CircleMember::query()
            ->where('user_id', $user->id)
            ->where(function (Builder $statusQuery): void {
                $statusQuery->whereNull('status')->orWhere('status', 'ILIKE', 'active');
            })
            ->pluck('circle_id')
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function userHasSegment(User $user, string $segment): bool
    {
        // TODO: Replace this fallback check with project-specific segment mapping once finalized.
        $segment = strtolower($segment);

        foreach (["is_{$segment}", $segment] as $flagKey) {
            $value = data_get($user, $flagKey);
            if (is_bool($value) && $value === true) {
                return true;
            }
            if (is_string($value) && in_array(strtolower($value), ['1', 'yes', 'true', $segment], true)) {
                return true;
            }
        }

        foreach (['business_type', 'designation', 'short_bio', 'long_bio_html', 'company_name'] as $textColumn) {
            $value = strtolower((string) data_get($user, $textColumn));
            if ($value !== '' && str_contains($value, $segment)) {
                return true;
            }
        }

        return false;
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CollaborationPost;
use App\Models\CollaborationType;
use App\Support\AdminCircleScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CollaborationPostController extends Controller
{
    public function index(Request $request): View
    {
        $rowsPerPage = (int) $request->query('per_page', 20);
        if (! in_array($rowsPerPage, [10, 20, 50, 100], true)) {
            $rowsPerPage = 20;
        }

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'collaboration_type' => (string) $request->query('collaboration_type', 'all'),
            'title' => trim((string) $request->query('title', '')),
            'scope' => trim((string) $request->query('scope', '')),
            'preferred_mode' => trim((string) $request->query('preferred_mode', '')),
            'business_stage' => trim((string) $request->query('business_stage', '')),
            'year_in_operation' => trim((string) $request->query('year_in_operation', '')),
            'status' => (string) $request->query('status', 'all'),
            'per_page' => $rowsPerPage,
        ];

        $query = CollaborationPost::query()
            ->with(['user', 'collaborationType'])
            ->latest('created_at');

        AdminCircleScope::applyToActivityQuery($query, Auth::guard('admin')->user(), 'collaboration_posts.user_id', null);

        $this->applyFilters($query, $filters);

        $posts = $query->paginate($rowsPerPage)->withQueryString();

        $statuses = CollaborationPost::query()
            ->select('status')
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status')
            ->filter()
            ->values();

        $types = class_exists(CollaborationType::class)
            ? CollaborationType::query()->orderBy('name')->get(['id', 'name', 'slug'])
            : collect();

        return view('admin.collaborations.index', [
            'posts' => $posts,
            'filters' => $filters,
            'statuses' => $statuses,
            'types' => $types,
            'rowsPerPage' => $rowsPerPage,
            'total' => $posts->total(),
            'from' => $posts->firstItem(),
            'to' => $posts->lastItem(),
        ]);
    }

    public function show(string $id, Request $request): View
    {
        $post = CollaborationPost::query()
            ->with(['user', 'collaborationType'])
            ->findOrFail($id);

        if (! AdminCircleScope::userInScope(Auth::guard('admin')->user(), (string) $post->user_id)) {
            abort(403);
        }

        return view('admin.collaborations.show', [
            'post' => $post,
            'backUrl' => route('admin.collaborations.index', $request->query()),
        ]);
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $userColumns = $this->existingUserColumns([
            'name',
            'company_name',
            'company',
            'business_name',
            'city',
            'current_city',
            'location_city',
        ]);

        $titleColumns = $this->existingPostColumns(['title', 'collaboration_title', 'subject']);
        $scopeColumns = $this->existingPostColumns(['scope', 'collaboration_scope', 'scope_text']);
        $preferredModeColumns = $this->existingPostColumns(['preferred_mode', 'preferred_model', 'meeting_mode', 'mode']);
        $businessStageColumns = $this->existingPostColumns(['business_stage', 'stage', 'business_stage_text']);
        $yearOperationColumns = $this->existingPostColumns(['year_in_operation', 'years_in_operation', 'operating_years', 'years']);

        if ($filters['q'] !== '') {
            $value = $filters['q'];

            $query->where(function (Builder $inner) use ($value, $userColumns, $titleColumns, $scopeColumns) {
                if ($userColumns !== []) {
                    $inner->whereHas('user', function (Builder $userQuery) use ($value, $userColumns) {
                        $this->applyLikeAny($userQuery, $userColumns, $value);
                    });
                }

                $this->applyLikeAny($inner, $titleColumns, $value, $userColumns !== []);
                $this->applyLikeAny($inner, $scopeColumns, $value, ($userColumns !== [] || $titleColumns !== []));
            });
        }

        if ($filters['title'] !== '') {
            $this->applyLikeFilter($query, $titleColumns, $filters['title']);
        }

        if ($filters['scope'] !== '') {
            $this->applyLikeFilter($query, $scopeColumns, $filters['scope']);
        }

        if ($filters['preferred_mode'] !== '') {
            $this->applyLikeFilter($query, $preferredModeColumns, $filters['preferred_mode']);
        }

        if ($filters['business_stage'] !== '') {
            $this->applyLikeFilter($query, $businessStageColumns, $filters['business_stage']);
        }

        if ($filters['year_in_operation'] !== '') {
            $this->applyLikeFilter($query, $yearOperationColumns, $filters['year_in_operation'], true);
        }

        if ($filters['collaboration_type'] !== 'all' && $filters['collaboration_type'] !== '') {
            $type = $filters['collaboration_type'];
            $query->where(function (Builder $inner) use ($type) {
                $inner->where('collaboration_type_id', $type)
                    ->orWhere('collaboration_type', $type)
                    ->orWhereHas('collaborationType', function (Builder $typeQuery) use ($type) {
                        $typeQuery->where('id', $type)
                            ->orWhere('slug', $type);
                    });
            });
        }

        if ($filters['status'] !== 'all' && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }
    }

    private function applyLikeFilter(Builder $query, array $columns, string $value, bool $castAsText = false): void
    {
        if ($columns === []) {
            return;
        }

        $query->where(function (Builder $inner) use ($columns, $value, $castAsText) {
            $this->applyLikeAny($inner, $columns, $value, false, $castAsText);
        });
    }

    private function applyLikeAny(Builder $query, array $columns, string $value, bool $useOr = false, bool $castAsText = false): void
    {
        if ($columns === []) {
            return;
        }

        foreach (array_values($columns) as $index => $column) {
            $method = $this->pickMethod($useOr, $index);
            if ($castAsText) {
                $query->{$method . 'Raw'}("CAST({$column} AS TEXT) LIKE ?", ["%{$value}%"]);
                continue;
            }

            $query->{$method}($column, 'like', "%{$value}%");
        }
    }

    private function pickMethod(bool $useOr, int $index): string
    {
        return $index === 0 ? ($useOr ? 'orWhere' : 'where') : 'orWhere';
    }

    private function existingPostColumns(array $columns): array
    {
        return array_values(array_filter($columns, fn (string $column) => Schema::hasColumn('collaboration_posts', $column)));
    }

    private function existingUserColumns(array $columns): array
    {
        return array_values(array_filter($columns, fn (string $column) => Schema::hasColumn('users', $column)));
    }
}

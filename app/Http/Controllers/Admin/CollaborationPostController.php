<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CollaborationPost;
use App\Models\CollaborationType;
use App\Support\AdminCircleScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CollaborationPostController extends Controller
{
    public function index(Request $request): View
    {
        $rowsPerPage = (int) $request->query('per_page', 20);
        if (! in_array($rowsPerPage, [10, 20, 50, 100], true)) {
            $rowsPerPage = 20;
        }

        $filters = $this->collectFilters($request, $rowsPerPage);

        $query = CollaborationPost::query()
            ->with(['user', 'collaborationType'])
            ->latest('created_at');

        AdminCircleScope::applyToActivityQuery($query, Auth::guard('admin')->user(), 'collaboration_posts.user_id', null);

        $this->applyFilters($request, $query);

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
            ? CollaborationType::query()->select('id', 'slug', 'name')->orderBy('name')->get()
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

    public function export(Request $request)
    {
        $query = CollaborationPost::query()
            ->with(['user', 'collaborationType'])
            ->latest('created_at');

        AdminCircleScope::applyToActivityQuery($query, Auth::guard('admin')->user(), 'collaboration_posts.user_id', null);

        $this->applyFilters($request, $query);

        $selectedIds = collect((array) $request->input('selected_ids', []))
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->values()
            ->all();

        if ($selectedIds !== []) {
            $query->whereIn('id', $selectedIds);
        }

        $filename = 'collaborations_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'post_id',
                'peer_name',
                'peer_company',
                'peer_city',
                'collaboration_type',
                'title',
                'scope',
                'preferred_mode',
                'business_stage',
                'years_in_operation',
                'status',
                'created_at',
            ]);

            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $post) {
                    $user = $post->user;
                    $peerName = $user?->name
                        ?? $user?->display_name
                        ?? $post->peer_name
                        ?? $post->person_name
                        ?? $post->name
                        ?? '—';
                    $company = ($user?->company_name ?? $user?->company ?? $user?->business_name ?? null)
                        ?? $post->company
                        ?? $post->company_name
                        ?? $post->business_name
                        ?? '—';
                    $city = ($user?->city ?? $user?->current_city ?? $user?->location_city ?? null)
                        ?? $post->city
                        ?? $post->user_city
                        ?? '—';

                    fputcsv($out, [
                        $post->id,
                        $peerName,
                        $company,
                        $city,
                        $post->collaborationType?->name ?? $post->collaboration_type ?? '—',
                        $post->title ?? $post->collaboration_title ?? $post->subject ?? '—',
                        $post->scope ?? $post->collaboration_scope ?? $post->scope_text ?? '—',
                        $post->preferred_mode ?? $post->preferred_model ?? $post->meeting_mode ?? $post->mode ?? '—',
                        $post->business_stage ?? $post->stage ?? $post->business_stage_text ?? '—',
                        $post->year_in_operation ?? $post->years_in_operation ?? $post->operating_years ?? $post->years ?? '—',
                        $post->status ?? '—',
                        optional($post->created_at)->format('Y-m-d H:i:s') ?? '—',
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
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

    private function collectFilters(Request $request, int $rowsPerPage): array
    {
        return [
            'q' => trim((string) $request->query('q', '')),
            'collaboration_type' => (string) $request->query('collaboration_type', 'all'),
            'title' => trim((string) $request->query('title', '')),
            'scope' => trim((string) $request->query('scope', '')),
            'preferred_mode' => trim((string) $request->query('preferred_mode', '')),
            'business_stage' => trim((string) $request->query('business_stage', '')),
            'year_in_operation' => trim((string) $request->query('year_in_operation', '')),
            'status' => (string) $request->query('status', 'all'),
            'created_from' => (string) $request->query('created_from', ''),
            'created_to' => (string) $request->query('created_to', ''),
            'per_page' => $rowsPerPage,
        ];
    }

    private function applyFilters(Request $request, Builder $query): void
    {
        $filters = $this->collectFilters($request, (int) $request->query('per_page', 20));

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
            $typeInput = $filters['collaboration_type'];
            $slug = $typeInput;

            if (Str::isUuid($typeInput) && class_exists(CollaborationType::class)) {
                $type = CollaborationType::query()->find($typeInput);
                if ($type) {
                    $slug = (string) ($type->slug ?? '');
                } else {
                    $query->whereRaw('1 = 0');
                    return;
                }
            }

            if ($slug === '') {
                $query->whereRaw('1 = 0');
                return;
            }

            if (Schema::hasColumn('collaboration_posts', 'collaboration_type')) {
                $query->where('collaboration_type', $slug);
            } elseif (Schema::hasColumn('collaboration_posts', 'collaboration_type_id')) {
                $query->where('collaboration_type_id', $slug);
            }
        }

        if ($filters['status'] !== 'all' && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['created_from'] !== '') {
            $query->where('created_at', '>=', Carbon::parse($filters['created_from'])->startOfDay());
        }

        if ($filters['created_to'] !== '') {
            $query->where('created_at', '<=', Carbon::parse($filters['created_to'])->endOfDay());
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

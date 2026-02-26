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
            'phone' => trim((string) $request->query('phone', '')),
            'company' => trim((string) $request->query('company', '')),
            'collaboration_type' => (string) $request->query('collaboration_type', 'all'),
            'city' => trim((string) $request->query('city', '')),
            'status' => (string) $request->query('status', 'all'),
            'created_from' => (string) $request->query('created_from', ''),
            'created_to' => (string) $request->query('created_to', ''),
            'per_page' => $rowsPerPage,
        ];

        $query = CollaborationPost::query()->with(['user', 'collaborationType'])->latest('created_at');

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
        $hasPostCity = Schema::hasColumn('collaboration_posts', 'city');
        $userColumns = [
            'name' => Schema::hasColumn('users', 'name'),
            'display_name' => Schema::hasColumn('users', 'display_name'),
            'first_name' => Schema::hasColumn('users', 'first_name'),
            'last_name' => Schema::hasColumn('users', 'last_name'),
            'email' => Schema::hasColumn('users', 'email'),
            'phone' => Schema::hasColumn('users', 'phone'),
            'company_name' => Schema::hasColumn('users', 'company_name'),
            'company' => Schema::hasColumn('users', 'company'),
            'business_name' => Schema::hasColumn('users', 'business_name'),
            'city' => Schema::hasColumn('users', 'city'),
            'current_city' => Schema::hasColumn('users', 'current_city'),
        ];

        if ($filters['q'] !== '') {
            $q = $filters['q'];

            $query->where(function (Builder $inner) use ($q, $hasPostCity, $userColumns) {
                $inner->whereHas('user', function (Builder $userQuery) use ($q, $userColumns) {
                    $userQuery->where(function (Builder $u) use ($q, $userColumns) {
                        $applied = false;
                        foreach (['name', 'display_name', 'first_name', 'last_name', 'email', 'company_name', 'company', 'business_name', 'city', 'current_city'] as $column) {
                            if (! $userColumns[$column]) {
                                continue;
                            }

                            $method = $applied ? 'orWhere' : 'where';
                            $u->{$method}($column, 'like', "%{$q}%");
                            $applied = true;
                        }

                        if (! $applied) {
                            $u->whereRaw('1 = 0');
                        }
                    });
                })->orWhere('title', 'like', "%{$q}%");

                if ($hasPostCity) {
                    $inner->orWhere('city', 'like', "%{$q}%");
                }
            });
        }

        if ($filters['phone'] !== '') {
            if ($userColumns['phone']) {
                $query->whereHas('user', fn (Builder $u) => $u->where('phone', 'like', "%{$filters['phone']}%"));
            }
        }

        if ($filters['company'] !== '') {
            $company = $filters['company'];
            $query->whereHas('user', function (Builder $u) use ($company, $userColumns) {
                $applied = false;
                foreach (['company_name', 'company', 'business_name'] as $column) {
                    if (! $userColumns[$column]) {
                        continue;
                    }

                    $method = $applied ? 'orWhere' : 'where';
                    $u->{$method}($column, 'like', "%{$company}%");
                    $applied = true;
                }

                if (! $applied) {
                    $u->whereRaw('1 = 0');
                }
            });
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

        if ($filters['city'] !== '') {
            $city = $filters['city'];
            $query->where(function (Builder $inner) use ($city, $hasPostCity, $userColumns) {
                if ($hasPostCity) {
                    $inner->where('city', 'like', "%{$city}%");
                }

                $inner->orWhereHas('user', function (Builder $u) use ($city, $userColumns) {
                    $applied = false;
                    foreach (['city', 'current_city'] as $column) {
                        if (! $userColumns[$column]) {
                            continue;
                        }

                        $method = $applied ? 'orWhere' : 'where';
                        $u->{$method}($column, 'like', "%{$city}%");
                        $applied = true;
                    }

                    if (! $applied) {
                        $u->whereRaw('1 = 0');
                    }
                });
            });
        }

        if ($filters['status'] !== 'all' && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['created_from'] !== '') {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if ($filters['created_to'] !== '') {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }
    }
}

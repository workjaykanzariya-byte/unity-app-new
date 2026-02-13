<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LoginHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'circle' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'in:10,20,50,100'],
        ]);

        $name = trim((string) ($validated['name'] ?? ''));
        $city = trim((string) ($validated['city'] ?? ''));
        $company = trim((string) ($validated['company'] ?? ''));
        $circle = trim((string) ($validated['circle'] ?? ''));
        $from = isset($validated['from']) && $validated['from'] !== ''
            ? Carbon::parse($validated['from'])
            : null;
        $to = isset($validated['to']) && $validated['to'] !== ''
            ? Carbon::parse($validated['to'])
            : null;
        $perPage = (int) ($validated['per_page'] ?? 20);

        $hasUsersName = Schema::hasColumn('users', 'name');
        $hasUsersCompany = Schema::hasColumn('users', 'company');

        $loginLastSubquery = DB::table('user_login_histories')
            ->select('user_id', DB::raw('MAX(logged_in_at) as last_login_at'))
            ->when($from, fn ($query) => $query->where('logged_in_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('logged_in_at', '<=', $to))
            ->groupBy('user_id');

        $records = DB::query()
            ->fromSub($loginLastSubquery, 'login_last')
            ->join('users', 'users.id', '=', 'login_last.user_id')
            ->leftJoin('cities', 'cities.id', '=', 'users.city_id')
            ->leftJoin('circle_members', function ($join) {
                $join->on('circle_members.user_id', '=', 'users.id')
                    ->whereNull('circle_members.deleted_at')
                    ->where('circle_members.status', '=', 'approved');
            })
            ->leftJoin('circles', 'circles.id', '=', 'circle_members.circle_id')
            ->when($name !== '', function ($query) use ($name, $hasUsersName) {
                $likeQuery = '%' . $name . '%';

                $query->where(function ($innerQuery) use ($likeQuery, $hasUsersName) {
                    $innerQuery->where('users.display_name', 'ilike', $likeQuery)
                        ->orWhere('users.email', 'ilike', $likeQuery);

                    if ($hasUsersName) {
                        $innerQuery->orWhere('users.name', 'ilike', $likeQuery);
                    }
                });
            })
            ->when($city !== '', function ($query) use ($city) {
                $likeQuery = '%' . $city . '%';

                $query->where(function ($innerQuery) use ($likeQuery) {
                    $innerQuery->where('users.city', 'ilike', $likeQuery)
                        ->orWhere('cities.name', 'ilike', $likeQuery);
                });
            })
            ->when($company !== '', function ($query) use ($company, $hasUsersCompany) {
                $likeQuery = '%' . $company . '%';

                $query->where(function ($innerQuery) use ($likeQuery, $hasUsersCompany) {
                    $innerQuery->where('users.company_name', 'ilike', $likeQuery);

                    if ($hasUsersCompany) {
                        $innerQuery->orWhere('users.company', 'ilike', $likeQuery);
                    }
                });
            })
            ->when($circle !== '', function ($query) use ($circle) {
                $likeQuery = '%' . $circle . '%';

                $query->whereExists(function ($existsQuery) use ($likeQuery) {
                    $existsQuery->selectRaw('1')
                        ->from('circle_members as circle_members_filter')
                        ->join('circles as circles_filter', 'circles_filter.id', '=', 'circle_members_filter.circle_id')
                        ->whereColumn('circle_members_filter.user_id', 'users.id')
                        ->whereNull('circle_members_filter.deleted_at')
                        ->where('circle_members_filter.status', '=', 'approved')
                        ->where('circles_filter.name', 'ilike', $likeQuery);
                });
            })
            ->selectRaw("\n                users.id,\n                users.display_name,\n                users.email,\n                COALESCE(NULLIF(users.city, ''), cities.name) as city,\n                users.company_name,\n                login_last.last_login_at,\n                COUNT(DISTINCT circles.id) as circles_count,\n                COALESCE(STRING_AGG(DISTINCT circles.name, ', '), '') as circles_names\n            ")
            ->groupBy(
                'users.id',
                'users.display_name',
                'users.email',
                'users.city',
                'cities.name',
                'users.company_name',
                'login_last.last_login_at'
            )
            ->orderByDesc('login_last.last_login_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.login_history.index', [
            'records' => $records,
            'filters' => [
                'name' => $name,
                'city' => $city,
                'company' => $company,
                'circle' => $circle,
                'from' => $from?->format('Y-m-d\TH:i') ?? '',
                'to' => $to?->format('Y-m-d\TH:i') ?? '',
                'per_page' => $perPage,
            ],
        ]);
    }
}

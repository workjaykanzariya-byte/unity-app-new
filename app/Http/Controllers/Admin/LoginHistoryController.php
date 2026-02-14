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
            'from' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'to' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'joined' => ['nullable', 'in:all,yes,no'],
            'per_page' => ['nullable', 'integer', 'in:10,20,50,100'],
        ]);

        $name = trim((string) ($validated['name'] ?? ''));
        $city = trim((string) ($validated['city'] ?? ''));
        $company = trim((string) ($validated['company'] ?? ''));
        $circle = trim((string) ($validated['circle'] ?? ''));
        $joined = (string) ($validated['joined'] ?? 'all');
        $perPage = (int) ($validated['per_page'] ?? 20);

        $fromInput = (string) ($validated['from'] ?? '');
        $toInput = (string) ($validated['to'] ?? '');

        $from = $fromInput !== ''
            ? Carbon::createFromFormat('Y-m-d H:i:s', str_replace('T', ' ', $fromInput) . ':00')
            : null;
        $to = $toInput !== ''
            ? Carbon::createFromFormat('Y-m-d H:i:s', str_replace('T', ' ', $toInput) . ':00')
            : null;

        $hasUsersName = Schema::hasColumn('users', 'name');
        $hasUsersCompany = Schema::hasColumn('users', 'company');

        $peerNameExpression = $hasUsersName
            ? "COALESCE(NULLIF(users.display_name, ''), users.name)"
            : "NULLIF(users.display_name, '')";

        $companyExpression = $hasUsersCompany
            ? "COALESCE(users.company_name, users.company, '')"
            : "COALESCE(users.company_name, '')";

        $loginLastSubquery = DB::table('user_login_histories')
            ->select('user_id', DB::raw('MAX(logged_in_at) as last_login_at'))
            ->when($from, fn ($query) => $query->where('logged_in_at', '>=', $from->format('Y-m-d H:i:s')))
            ->when($to, fn ($query) => $query->where('logged_in_at', '<=', $to->format('Y-m-d H:i:s')))
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
            ->selectRaw("\n                users.id,\n                {$peerNameExpression} as peer_name,\n                users.email,\n                COALESCE(NULLIF(users.city, ''), cities.name) as city,\n                {$companyExpression} as company,\n                login_last.last_login_at,\n                COUNT(DISTINCT circles.id) as circles_count,\n                COALESCE(STRING_AGG(DISTINCT circles.name, ', '), '') as circles_names,\n                CASE WHEN COUNT(DISTINCT circles.id) > 0 THEN true ELSE false END as has_circle\n            ")
            ->groupBy(
                'users.id',
                'users.display_name',
                'users.email',
                'users.city',
                'cities.name',
                'users.company_name',
                'login_last.last_login_at',
                ...($hasUsersName ? ['users.name'] : []),
                ...($hasUsersCompany ? ['users.company'] : [])
            )
            ->when($joined === 'yes', fn ($query) => $query->havingRaw('COUNT(DISTINCT circles.id) > 0'))
            ->when($joined === 'no', fn ($query) => $query->havingRaw('COUNT(DISTINCT circles.id) = 0'))
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
                'from' => $fromInput,
                'to' => $toInput,
                'joined' => in_array($joined, ['all', 'yes', 'no'], true) ? $joined : 'all',
                'per_page' => $perPage,
            ],
        ]);
    }
}

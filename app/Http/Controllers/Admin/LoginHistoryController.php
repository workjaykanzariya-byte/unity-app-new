<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoginHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $from = isset($validated['from']) && $validated['from'] !== ''
            ? Carbon::parse($validated['from'])
            : null;

        $to = isset($validated['to']) && $validated['to'] !== ''
            ? Carbon::parse($validated['to'])
            : null;

        $queryText = trim((string) ($validated['q'] ?? ''));

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
            ->when($queryText !== '', function ($query) use ($queryText) {
                $query->where(function ($innerQuery) use ($queryText) {
                    $likeQuery = '%' . $queryText . '%';

                    $innerQuery->where('users.display_name', 'ilike', $likeQuery)
                        ->orWhere('users.first_name', 'ilike', $likeQuery)
                        ->orWhere('users.last_name', 'ilike', $likeQuery)
                        ->orWhere('users.email', 'ilike', $likeQuery)
                        ->orWhere('users.phone', 'ilike', $likeQuery);
                });
            })
            ->selectRaw("\n                users.id,\n                users.display_name,\n                users.email,\n                users.phone,\n                COALESCE(NULLIF(users.city, ''), cities.name) as city,\n                users.company_name,\n                login_last.last_login_at,\n                COUNT(DISTINCT circles.id) as circles_count,\n                COALESCE(STRING_AGG(DISTINCT circles.name, ', '), '') as circles_names\n            ")
            ->groupBy(
                'users.id',
                'users.display_name',
                'users.email',
                'users.phone',
                'users.city',
                'cities.name',
                'users.company_name',
                'login_last.last_login_at'
            )
            ->orderByDesc('login_last.last_login_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.login_history.index', [
            'records' => $records,
            'filters' => [
                'q' => $queryText,
                'from' => $from?->format('Y-m-d\TH:i') ?? '',
                'to' => $to?->format('Y-m-d\TH:i') ?? '',
            ],
        ]);
    }
}

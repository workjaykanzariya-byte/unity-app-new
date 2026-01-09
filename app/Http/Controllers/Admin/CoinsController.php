<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CoinLedger;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CoinsController extends Controller
{
    private const ACTIVITY_TABLES = [
        'testimonials' => 'testimonials',
        'referrals' => 'referrals',
        'business_deals' => 'business_deals',
        'p2p_meetings' => 'p2p_meetings',
        'requirements' => 'requirements',
    ];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', $request->query('search', '')));
        $membership = $request->query('membership_status');
        $perPage = $request->integer('per_page') ?: 20;
        $perPage = in_array($perPage, [10, 20, 25, 50, 100], true) ? $perPage : 20;

        $query = User::query()->select([
            'id',
            'email',
            'first_name',
            'last_name',
            'display_name',
            'membership_status',
        ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $like = "%{$search}%";
                $q->where('display_name', 'ILIKE', $like)
                    ->orWhere('first_name', 'ILIKE', $like)
                    ->orWhere('last_name', 'ILIKE', $like)
                    ->orWhere('email', 'ILIKE', $like);
            });
        }

        if ($membership && $membership !== 'all') {
            $query->where('membership_status', $membership);
        }

        $members = $query->orderBy('display_name')->paginate($perPage)->withQueryString();
        $memberIds = $members->pluck('id')->all();

        $totalCoins = $this->sumByUser(DB::table('coins_ledger')
            ->whereIn('user_id', $memberIds), 'user_id', 'user_id');

        $activityCoins = [];
        foreach (self::ACTIVITY_TABLES as $type => $table) {
            $activityCoins[$type] = $this->sumByUser(DB::table('coins_ledger')
                ->join($table, 'coins_ledger.activity_id', '=', $table . '.id')
                ->whereIn('coins_ledger.user_id', $memberIds), 'coins_ledger.user_id', 'user_id');
        }

        $membershipStatuses = User::query()
            ->whereNotNull('membership_status')
            ->distinct()
            ->pluck('membership_status')
            ->sort()
            ->values();

        return view('admin.coins.index', [
            'members' => $members,
            'filters' => [
                'search' => $search,
                'membership_status' => $membership,
                'per_page' => $perPage,
            ],
            'membershipStatuses' => $membershipStatuses,
            'totals' => $totalCoins,
            'activityTotals' => $activityCoins,
        ]);
    }

    public function ledger(User $member, Request $request): View
    {
        $filters = $this->dateFilters($request);

        $query = CoinLedger::query()
            ->where('user_id', $member->id)
            ->when($filters['from'], fn ($q) => $q->whereDate('created_at', '>=', $filters['from']))
            ->when($filters['to'], fn ($q) => $q->whereDate('created_at', '<=', $filters['to']))
            ->orderByDesc('created_at');

        $items = $query->paginate(20)->withQueryString();

        return view('admin.coins.ledger', $this->ledgerViewData($member, $items, $filters));
    }

    public function ledgerByType(User $member, string $type, Request $request): View
    {
        if (! array_key_exists($type, self::ACTIVITY_TABLES)) {
            abort(404);
        }

        $filters = $this->dateFilters($request);
        $table = self::ACTIVITY_TABLES[$type];

        $query = CoinLedger::query()
            ->where('user_id', $member->id)
            ->whereExists(function ($subquery) use ($table) {
                $subquery->select(DB::raw(1))
                    ->from($table)
                    ->whereColumn('coins_ledger.activity_id', $table . '.id');
            })
            ->when($filters['from'], fn ($q) => $q->whereDate('created_at', '>=', $filters['from']))
            ->when($filters['to'], fn ($q) => $q->whereDate('created_at', '<=', $filters['to']))
            ->orderByDesc('created_at');

        $items = $query->paginate(20)->withQueryString();

        return view('admin.coins.ledger', array_merge(
            $this->ledgerViewData($member, $items, $filters),
            ['activeType' => $type]
        ));
    }

    private function sumByUser($query, string $column, string $alias): array
    {
        return $query
            ->select(DB::raw($column . ' as ' . $alias), DB::raw('sum(amount) as total'))
            ->groupBy($column)
            ->pluck('total', $alias)
            ->all();
    }

    private function ledgerViewData(User $member, $items, array $filters): array
    {
        $activityTypes = $this->resolveActivityTypes($items->pluck('activity_id')->filter()->unique()->all());
        $createdBy = $this->resolveCreatedByUsers($items->pluck('created_by')->filter()->unique()->all());

        return [
            'member' => $member,
            'items' => $items,
            'filters' => $filters,
            'activityTypes' => $activityTypes,
            'createdByUsers' => $createdBy,
            'activeType' => null,
        ];
    }

    private function resolveActivityTypes(array $activityIds): array
    {
        if ($activityIds === []) {
            return [];
        }

        $types = [];

        foreach (self::ACTIVITY_TABLES as $type => $table) {
            $ids = DB::table($table)
                ->whereIn('id', $activityIds)
                ->pluck('id')
                ->all();

            foreach ($ids as $id) {
                $types[$id] = $type;
            }
        }

        return $types;
    }

    private function resolveCreatedByUsers(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        return User::query()
            ->select(['id', 'email', 'first_name', 'last_name', 'display_name'])
            ->whereIn('id', $userIds)
            ->get()
            ->keyBy('id')
            ->all();
    }

    private function dateFilters(Request $request): array
    {
        return [
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];
    }
}

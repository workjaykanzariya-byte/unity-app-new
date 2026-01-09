<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessDeal;
use App\Models\CoinLedger;
use App\Models\P2pMeeting;
use App\Models\Referral;
use App\Models\Requirement;
use App\Models\Testimonial;
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

        $ledgerTable = (new CoinLedger())->getTable();
        $testimonialsTable = (new Testimonial())->getTable();
        $referralsTable = (new Referral())->getTable();
        $businessDealsTable = (new BusinessDeal())->getTable();
        $p2pMeetingsTable = (new P2pMeeting())->getTable();
        $requirementsTable = (new Requirement())->getTable();

        $coinsByUserId = DB::table($ledgerTable . ' as cl')
            ->leftJoin($testimonialsTable . ' as t', 't.id', '=', 'cl.activity_id')
            ->leftJoin($referralsTable . ' as r', 'r.id', '=', 'cl.activity_id')
            ->leftJoin($businessDealsTable . ' as bd', 'bd.id', '=', 'cl.activity_id')
            ->leftJoin($p2pMeetingsTable . ' as pm', 'pm.id', '=', 'cl.activity_id')
            ->leftJoin($requirementsTable . ' as req', 'req.id', '=', 'cl.activity_id')
            ->whereIn('cl.user_id', $memberIds)
            ->select([
                'cl.user_id',
                DB::raw('sum(cl.amount) as total_coins'),
                DB::raw("sum(case when t.id is not null then cl.amount when cl.reference ilike '%testimonial%' then cl.amount else 0 end) as testimonials_coins"),
                DB::raw("sum(case when r.id is not null then cl.amount when cl.reference ilike '%referral%' then cl.amount else 0 end) as referrals_coins"),
                DB::raw("sum(case when bd.id is not null then cl.amount when cl.reference ilike '%business deal%' or cl.reference ilike '%deal%' then cl.amount else 0 end) as business_deals_coins"),
                DB::raw("sum(case when pm.id is not null then cl.amount when cl.reference ilike '%p2p%' or cl.reference ilike '%meeting%' then cl.amount else 0 end) as p2p_meetings_coins"),
                DB::raw("sum(case when req.id is not null then cl.amount when cl.reference ilike '%requirement%' then cl.amount else 0 end) as requirements_coins"),
            ])
            ->groupBy('cl.user_id')
            ->get()
            ->keyBy('user_id')
            ->all();

        $sampleUserId = collect($coinsByUserId)
            ->first(fn ($row) => (float) ($row->total_coins ?? 0) > 0)
            ->user_id ?? null;

        if ($sampleUserId) {
            $nullActivityCount = DB::table($ledgerTable)
                ->where('user_id', $sampleUserId)
                ->whereNull('activity_id')
                ->count();

            $topReferences = DB::table($ledgerTable)
                ->where('user_id', $sampleUserId)
                ->whereNotNull('reference')
                ->orderByDesc('created_at')
                ->limit(5)
                ->pluck('reference')
                ->all();

            logger()->info('Admin coins summary debug sample', [
                'user_id' => $sampleUserId,
                'null_activity_id_count' => $nullActivityCount,
                'top_references' => $topReferences,
            ]);
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
            'coinsByUserId' => $coinsByUserId,
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

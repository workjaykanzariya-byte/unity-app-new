<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessDeal;
use App\Models\P2pMeeting;
use App\Models\Referral;
use App\Models\Requirement;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ActivitiesController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
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

        $testimonialCounts = $this->countByUser(Testimonial::query()
            ->whereIn('from_user_id', $memberIds)
            ->where('is_deleted', false)
            ->whereNull('deleted_at'),
            'from_user_id');

        $referralCounts = $this->countByUser(Referral::query()
            ->whereIn('from_user_id', $memberIds)
            ->where('is_deleted', false)
            ->whereNull('deleted_at'),
            'from_user_id');

        $businessDealCounts = $this->countByUser(BusinessDeal::query()
            ->whereIn('from_user_id', $memberIds)
            ->where('is_deleted', false)
            ->whereNull('deleted_at'),
            'from_user_id');

        $p2pMeetingCounts = $this->countByUser(P2pMeeting::query()
            ->whereIn('initiator_user_id', $memberIds)
            ->where('is_deleted', false)
            ->whereNull('deleted_at'),
            'initiator_user_id');

        $requirementCounts = $this->countByUser(Requirement::query()
            ->whereIn('user_id', $memberIds)
            ->whereNull('deleted_at'),
            'user_id');

        $membershipStatuses = User::query()
            ->whereNotNull('membership_status')
            ->distinct()
            ->pluck('membership_status')
            ->sort()
            ->values();

        return view('admin.activities.index', [
            'members' => $members,
            'filters' => [
                'search' => $search,
                'membership_status' => $membership,
                'per_page' => $perPage,
            ],
            'membershipStatuses' => $membershipStatuses,
            'counts' => [
                'testimonials' => $testimonialCounts,
                'referrals' => $referralCounts,
                'business_deals' => $businessDealCounts,
                'p2p_meetings' => $p2pMeetingCounts,
                'requirements' => $requirementCounts,
            ],
        ]);
    }

    public function testimonials(User $member): View
    {
        $items = Testimonial::query()
            ->with(['toUser'])
            ->where('from_user_id', $member->id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.activities.list-testimonials', [
            'member' => $member,
            'items' => $items,
        ]);
    }

    public function referrals(User $member): View
    {
        $items = Referral::query()
            ->with(['toUser'])
            ->where('from_user_id', $member->id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.activities.list-referrals', [
            'member' => $member,
            'items' => $items,
        ]);
    }

    public function businessDeals(User $member): View
    {
        $items = BusinessDeal::query()
            ->with(['toUser'])
            ->where('from_user_id', $member->id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.activities.list-business-deals', [
            'member' => $member,
            'items' => $items,
        ]);
    }

    public function p2pMeetings(User $member): View
    {
        $items = P2pMeeting::query()
            ->with(['peer'])
            ->where('initiator_user_id', $member->id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->orderByDesc('meeting_date')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.activities.list-p2p-meetings', [
            'member' => $member,
            'items' => $items,
        ]);
    }

    public function requirements(User $member): View
    {
        $items = Requirement::query()
            ->where('user_id', $member->id)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.activities.list-requirements', [
            'member' => $member,
            'items' => $items,
        ]);
    }

    private function countByUser($query, string $column): array
    {
        return $query
            ->select($column, DB::raw('count(*) as total'))
            ->groupBy($column)
            ->pluck('total', $column)
            ->all();
    }
}

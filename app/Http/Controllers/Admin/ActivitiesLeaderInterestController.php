<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaderInterestSubmission;
use App\Support\AdminCircleScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ActivitiesLeaderInterestController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $applyingFor = $request->query('applying_for', 'all');

        $query = LeaderInterestSubmission::query()
            ->with(['user:id,display_name,first_name,last_name,phone']);

        if ($applyingFor && $applyingFor !== 'all') {
            $query->where('applying_for', $applyingFor);
        }

        if ($search !== '') {
            $like = "%{$search}%";
            $query->where(function ($q) use ($like) {
                $q->where('referred_name', 'ILIKE', $like)
                    ->orWhere('referred_mobile', 'ILIKE', $like)
                    ->orWhere('contribute_city', 'ILIKE', $like)
                    ->orWhereHas('user', function ($userQuery) use ($like) {
                        $userQuery->where('display_name', 'ILIKE', $like)
                            ->orWhere('first_name', 'ILIKE', $like)
                            ->orWhere('last_name', 'ILIKE', $like)
                            ->orWhere('phone', 'ILIKE', $like);
                    });
            });
        }

        AdminCircleScope::applyToActivityQuery($query, Auth::guard('admin')->user(), 'leader_interest_submissions.user_id', null);

        $items = $query
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.activities.become_a_leader.index', [
            'items' => $items,
            'filters' => [
                'search' => $search,
                'applying_for' => $applyingFor,
            ],
            'applyingForOptions' => ['all', 'myself', 'referring_friend'],
        ]);
    }
}

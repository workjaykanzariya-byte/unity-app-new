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
        $query = LeaderInterestSubmission::query()
            ->with(['user:id,display_name,first_name,last_name']);

        AdminCircleScope::applyToActivityQuery($query, Auth::guard('admin')->user(), 'leader_interest_submissions.user_id', null);

        $items = $query
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.activities.become_a_leader.index', [
            'items' => $items,
        ]);
    }
}

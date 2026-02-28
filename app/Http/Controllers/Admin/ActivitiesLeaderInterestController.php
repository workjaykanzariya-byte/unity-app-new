<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\LeaderInterestSubmission;
use App\Models\User;
use App\Support\AdminCircleScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ActivitiesLeaderInterestController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', $request->query('search', '')));
        $from = $request->query('from');
        $to = $request->query('to');
        $fromAt = $this->parseDayBoundary($from, false);
        $toAt = $this->parseDayBoundary($to, true);
        $applyingFor = $request->query('applying_for', 'all');

        $query = LeaderInterestSubmission::query()
            ->with(['user:id,display_name,first_name,last_name,phone,email,company_name,city']);

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
                            ->orWhere('phone', 'ILIKE', $like)
                            ->orWhere('email', 'ILIKE', $like)
                            ->orWhere('company_name', 'ILIKE', $like)
                            ->orWhere('city', 'ILIKE', $like);
                    });
            });
        }

        if ($fromAt) {
            $query->where('created_at', '>=', $fromAt);
        }

        if ($toAt) {
            $query->where('created_at', '<=', $toAt);
        }

        AdminCircleScope::applyToActivityQuery($query, Auth::guard('admin')->user(), 'leader_interest_submissions.user_id', null);

        $items = $query
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.activities.become_a_leader.index', [
            'items' => $items,
            'filters' => [
                'q' => $search,
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    private function parseDayBoundary($value, bool $endOfDay): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            $parsed = Carbon::parse($value);

            return $endOfDay ? $parsed->endOfDay() : $parsed->startOfDay();
        } catch (\Throwable $exception) {
            return null;
        }
    }

    public function show(User $peer, Request $request): View
    {
        $admin = Auth::guard('admin')->user();

        if (! AdminCircleScope::userInScope($admin, $peer->id)) {
            abort(403);
        }

        $items = LeaderInterestSubmission::query()
            ->where('user_id', $peer->id)
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.activities.become_a_leader.show', [
            'peer' => $peer,
            'items' => $items,
        ]);
    }
}

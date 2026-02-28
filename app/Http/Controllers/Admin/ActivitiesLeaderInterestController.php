<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\LeaderInterestSubmission;
use App\Models\User;
use App\Support\AdminCircleScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            ->leftJoin('users as peer', 'peer.id', '=', 'leader_interest_submissions.user_id')
            ->select([
                'leader_interest_submissions.*',
                DB::raw("coalesce(nullif(trim(concat_ws(' ', peer.first_name, peer.last_name)), ''), peer.display_name, '—') as peer_name"),
                DB::raw("coalesce(peer.company_name, '') as peer_company"),
                DB::raw("coalesce(peer.city, '') as peer_city"),
                'peer.phone as peer_phone',
            ]);

        if ($applyingFor && $applyingFor !== 'all') {
            $query->where('applying_for', $applyingFor);
        }

        if ($search !== '') {
            $like = "%{$search}%";
            $query->where(function ($q) use ($like) {
                $q->where('referred_name', 'ILIKE', $like)
                    ->orWhere('referred_mobile', 'ILIKE', $like)
                    ->orWhere('contribute_city', 'ILIKE', $like)
                    ->orWhere('peer.display_name', 'ILIKE', $like)
                    ->orWhere('peer.first_name', 'ILIKE', $like)
                    ->orWhere('peer.last_name', 'ILIKE', $like)
                    ->orWhere('peer.phone', 'ILIKE', $like)
                    ->orWhere('peer.email', 'ILIKE', $like)
                    ->orWhere('peer.company_name', 'ILIKE', $like)
                    ->orWhere('peer.city', 'ILIKE', $like);
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

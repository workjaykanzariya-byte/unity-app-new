<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\PeerRecommendation;
use App\Models\User;
use App\Support\AdminCircleScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ActivitiesPeerRecommendationController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', $request->query('search', '')));
        $from = $request->query('from');
        $to = $request->query('to');
        $fromAt = $this->parseDayBoundary($from, false);
        $toAt = $this->parseDayBoundary($to, true);
        $known = $request->query('how_well_known', 'all');

        $query = PeerRecommendation::query()
            ->with(['user:id,display_name,first_name,last_name,phone,email,company_name,city']);

        if ($known && $known !== 'all') {
            $query->where('how_well_known', $known);
        }

        if ($search !== '') {
            $like = "%{$search}%";
            $query->where(function ($q) use ($like) {
                $q->where('peer_name', 'ILIKE', $like)
                    ->orWhere('peer_mobile', 'ILIKE', $like)
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

        AdminCircleScope::applyToActivityQuery($query, Auth::guard('admin')->user(), 'peer_recommendations.user_id', null);

        $items = $query
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.activities.recommend_peer.index', [
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

        $items = PeerRecommendation::query()
            ->where('user_id', $peer->id)
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.activities.recommend_peer.show', [
            'peer' => $peer,
            'items' => $items,
        ]);
    }
}

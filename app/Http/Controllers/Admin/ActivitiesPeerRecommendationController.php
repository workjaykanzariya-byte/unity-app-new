<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\PeerRecommendation;
use App\Models\User;
use App\Support\AdminCircleScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            ->leftJoin('users as peer', 'peer.id', '=', 'peer_recommendations.user_id')
            ->select([
                'peer_recommendations.*',
                DB::raw("coalesce(nullif(trim(concat_ws(' ', peer.first_name, peer.last_name)), ''), peer.display_name, '—') as from_user_name"),
                DB::raw("coalesce(peer.company_name, '') as from_company"),
                DB::raw("coalesce(peer.city, '') as from_city"),
                'peer.phone as from_phone',
            ]);

        if ($known && $known !== 'all') {
            $query->where('how_well_known', $known);
        }

        if ($search !== '') {
            $like = "%{$search}%";
            $query->where(function ($q) use ($like) {
                $q->where('peer_recommendations.peer_name', 'ILIKE', $like)
                    ->orWhere('peer_mobile', 'ILIKE', $like)
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

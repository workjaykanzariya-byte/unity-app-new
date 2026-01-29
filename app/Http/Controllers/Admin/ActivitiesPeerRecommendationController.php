<?php

namespace App\Http\Controllers\Admin;

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
        $search = trim((string) $request->query('search', ''));
        $known = $request->query('how_well_known', 'all');
        $status = $request->query('status', 'pending');

        $query = PeerRecommendation::query()
            ->with(['user:id,display_name,first_name,last_name,phone']);

        if ($known && $known !== 'all') {
            $query->where('how_well_known', $known);
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
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
                            ->orWhere('phone', 'ILIKE', $like);
                    });
            });
        }

        AdminCircleScope::applyToActivityQuery($query, Auth::guard('admin')->user(), 'peer_recommendations.user_id', null);

        $items = $query
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.activities.recommend_peer.index', [
            'items' => $items,
            'filters' => [
                'search' => $search,
                'how_well_known' => $known,
                'status' => $status,
            ],
            'knownOptions' => [
                'all',
                'close_friend',
                'business_associate',
                'client',
                'community_contact',
            ],
            'statusOptions' => [
                'all',
                'pending',
                'approved',
                'rejected',
            ],
        ]);
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

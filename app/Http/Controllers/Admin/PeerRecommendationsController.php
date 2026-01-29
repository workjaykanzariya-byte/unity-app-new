<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PeerRecommendation;
use App\Services\Coins\CoinsService;
use App\Support\AdminCircleScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PeerRecommendationsController extends Controller
{
    public function approve(string $id, CoinsService $coinsService): RedirectResponse
    {
        if (! Str::isUuid($id)) {
            abort(404);
        }

        $admin = Auth::guard('admin')->user();
        $message = DB::transaction(function () use ($id, $admin, $coinsService) {
            $recommendation = PeerRecommendation::query()
                ->where('id', $id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! AdminCircleScope::userInScope($admin, $recommendation->user_id)) {
                abort(403);
            }

            $status = $recommendation->status ?? 'pending';

            if ($status === 'approved' || $recommendation->coins_awarded) {
                return 'Already approved.';
            }

            if ($status === 'rejected') {
                return 'Already rejected.';
            }

            $recommendation->status = 'approved';
            $recommendation->reviewed_at = now();
            $recommendation->reviewed_by_admin_user_id = $admin?->id;
            $recommendation->save();

            $amount = (int) config('coins.recommend_peer', 0);
            $ledger = $coinsService->reward($recommendation->user, $amount, 'Recommend A Peer (Approved)', [
                'reference' => 'recommend_peer:' . $recommendation->id,
                'created_by' => $admin?->id,
            ]);

            if ($ledger) {
                $recommendation->coins_awarded = true;
                $recommendation->coins_awarded_at = now();
                $recommendation->save();
            }

            return 'Recommendation approved.';
        });

        return redirect()
            ->back()
            ->with('success', $message);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendPushNotificationJob;
use App\Models\CoinClaimRequest;
use App\Models\CoinsLedger;
use App\Models\Notification;
use App\Models\User;
use App\Support\AdminCircleScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CoinClaimsController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'pending');
        $search = trim((string) $request->query('search', ''));

        $query = CoinClaimRequest::query()->with(['user:id,display_name,first_name,last_name,phone']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $like = "%{$search}%";
            $query->whereHas('user', function ($q) use ($like) {
                $q->where('display_name', 'ILIKE', $like)
                    ->orWhere('first_name', 'ILIKE', $like)
                    ->orWhere('last_name', 'ILIKE', $like)
                    ->orWhere('phone', 'ILIKE', $like);
            });
        }

        AdminCircleScope::applyToActivityQuery($query, Auth::guard('admin')->user(), 'coin_claim_requests.user_id', null);

        $claims = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        return view('admin.coin_claims.index', [
            'claims' => $claims,
            'filters' => compact('status', 'search'),
            'statuses' => ['pending', 'approved', 'rejected'],
            'activityLabels' => config('coins.claim_coin_labels', []),
        ]);
    }

    public function approve(string $id): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();

        $message = DB::transaction(function () use ($id, $admin) {
            $claim = CoinClaimRequest::query()->where('id', $id)->lockForUpdate()->firstOrFail();

            if (! AdminCircleScope::userInScope($admin, $claim->user_id)) {
                abort(403);
            }

            if ($claim->status !== 'pending') {
                return 'Only pending claims can be approved.';
            }

            if (CoinsLedger::query()->where('reference', 'claim_coin:'.$claim->id)->exists()) {
                return 'This claim already has a ledger transaction.';
            }

            $coins = (int) config('coins.claim_coin.'.$claim->activity_code, 0);
            if ($coins <= 0) {
                return 'Coin mapping is missing for this claim activity.';
            }

            $user = User::query()->where('id', $claim->user_id)->lockForUpdate()->firstOrFail();
            $newBalance = (int) $user->coins_balance + $coins;

            $user->update(['coins_balance' => $newBalance]);

            CoinsLedger::create([
                'transaction_id' => Str::uuid()->toString(),
                'user_id' => $user->id,
                'amount' => $coins,
                'balance_after' => $newBalance,
                'reference' => 'claim_coin:'.$claim->id,
                'created_by' => $admin?->id,
                'created_at' => now(),
            ]);

            $claim->update([
                'status' => 'approved',
                'coins_awarded' => $coins,
                'reviewed_by_admin_id' => $admin?->id,
                'reviewed_at' => now(),
            ]);

            $notification = Notification::create([
                'user_id' => $user->id,
                'type' => 'activity_update',
                'payload' => [
                    'notification_type' => 'coin_claim_approved',
                    'title' => 'Coins Added',
                    'body' => sprintf(
                        'Your coin claim for %s has been approved. +%d coins.',
                        config('coins.claim_coin_labels.'.$claim->activity_code, $claim->activity_code),
                        $coins
                    ),
                    'coin_claim_request_id' => (string) $claim->id,
                    'activity_code' => $claim->activity_code,
                    'coins_awarded' => $coins,
                ],
                'is_read' => false,
                'created_at' => now(),
            ]);

            SendPushNotificationJob::dispatch($user, 'Coins Added', sprintf('Your coin claim for %s has been approved. +%d coins.', config('coins.claim_coin_labels.'.$claim->activity_code, $claim->activity_code), $coins), [
                'type' => 'coin_claim_approved',
                'notification_id' => (string) $notification->id,
                'coin_claim_request_id' => (string) $claim->id,
                'activity_code' => $claim->activity_code,
                'coins_awarded' => $coins,
            ]);

            return 'Coin claim approved.';
        });

        return redirect()->back()->with('success', $message);
    }

    public function reject(Request $request, string $id): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();

        $message = DB::transaction(function () use ($request, $id, $admin) {
            $claim = CoinClaimRequest::query()->where('id', $id)->lockForUpdate()->firstOrFail();

            if (! AdminCircleScope::userInScope($admin, $claim->user_id)) {
                abort(403);
            }

            if ($claim->status !== 'pending') {
                return 'Only pending claims can be rejected.';
            }

            $claim->update([
                'status' => 'rejected',
                'reviewed_by_admin_id' => $admin?->id,
                'reviewed_at' => now(),
                'admin_note' => $request->input('admin_note'),
            ]);

            $user = User::find($claim->user_id);
            if ($user) {
                $activityLabel = config('coins.claim_coin_labels.'.$claim->activity_code, $claim->activity_code);
                $title = 'Coin Claim Rejected';
                $body = "Your coin claim for {$activityLabel} was rejected.";
                $notification = Notification::create([
                    'user_id' => $user->id,
                    'type' => 'activity_update',
                    'payload' => [
                        'notification_type' => 'coin_claim_rejected',
                        'title' => $title,
                        'body' => $body,
                        'coin_claim_request_id' => (string) $claim->id,
                        'activity_code' => $claim->activity_code,
                        'admin_note' => $request->input('admin_note'),
                    ],
                    'is_read' => false,
                    'created_at' => now(),
                ]);

                SendPushNotificationJob::dispatch($user, $title, $body, [
                    'type' => 'coin_claim_rejected',
                    'notification_id' => (string) $notification->id,
                    'coin_claim_request_id' => (string) $claim->id,
                    'activity_code' => $claim->activity_code,
                ]);
            }

            return 'Coin claim rejected.';
        });

        return redirect()->back()->with('success', $message);
    }
}

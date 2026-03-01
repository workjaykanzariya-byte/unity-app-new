<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CoinClaims\RejectCoinClaimRequest;
use App\Models\CoinClaimRequest;
use App\Notifications\CoinClaimReviewedNotification;
use App\Services\CoinClaims\CoinClaimEmailService;
use App\Services\Coins\CoinsService;
use App\Support\AdminCircleScope;
use App\Support\CoinClaims\CoinClaimActivityRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CoinClaimsController extends Controller
{
    public function __construct(
        private readonly CoinClaimActivityRegistry $registry,
        private readonly CoinsService $coinsService,
        private readonly CoinClaimEmailService $emailService,
    ) {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'pending');

        $query = CoinClaimRequest::query()
            ->with(['user:id,display_name,first_name,last_name,phone']);

        if ($status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $like = "%{$search}%";
            $query->whereHas('user', function ($userQuery) use ($like) {
                $userQuery->where('display_name', 'ILIKE', $like)
                    ->orWhere('first_name', 'ILIKE', $like)
                    ->orWhere('last_name', 'ILIKE', $like)
                    ->orWhere('phone', 'ILIKE', $like);
            });
        }

        AdminCircleScope::applyToActivityQuery($query, Auth::guard('admin')->user(), 'coin_claim_requests.user_id', null);

        $claims = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        return view('admin.coin_claims.index', [
            'claims' => $claims,
            'registry' => $this->registry,
            'filters' => ['search' => $search, 'status' => $status],
            'statuses' => ['pending', 'approved', 'rejected'],
        ]);
    }

    public function show(string $id): View
    {
        if (! Str::isUuid($id)) {
            abort(404);
        }

        $claim = CoinClaimRequest::with('user:id,display_name,first_name,last_name,phone,email')->findOrFail($id);

        if (! AdminCircleScope::userInScope(Auth::guard('admin')->user(), $claim->user_id)) {
            abort(403);
        }

        return view('admin.coin_claims.show', [
            'claim' => $claim,
            'activity' => $this->registry->get((string) $claim->activity_code),
        ]);
    }

    public function approve(string $id, Request $request): RedirectResponse
    {
        $requestId = (string) Str::uuid();
        Log::info('Coin claim approve requested', ['request_id' => $requestId, 'id' => $id]);

        $admin = Auth::guard('admin')->user();

        try {
            $message = DB::transaction(function () use ($id, $admin, $requestId, $request) {
                $claim = CoinClaimRequest::with('user')->where('id', $id)->lockForUpdate()->firstOrFail();

                if (! AdminCircleScope::userInScope($admin, $claim->user_id)) {
                    abort(403);
                }

                if ($claim->status !== 'pending') {
                    return 'Claim already reviewed.';
                }

                $activity = $this->registry->get((string) $claim->activity_code) ?? [];
                $coins = (int) ($activity['coins'] ?? 0);

                $claim->status = 'approved';
                $claim->reviewed_by_admin_id = $admin?->id;
                $claim->reviewed_at = now();
                $claim->coins_awarded = $coins;
                $claim->admin_note = $request->input('admin_note');
                $claim->save();

                if ($coins > 0 && $claim->user) {
                    $reference = 'Coin claim approved: ' . $claim->activity_code . ' #' . $claim->id
                        . ' | admin: ' . ($admin?->id ?? 'unknown');

                    Log::info('coin_claim.approve.ledger_payload', [
                        'claim_id' => (string) $claim->id,
                        'user_id' => (string) $claim->user_id,
                        'amount' => $coins,
                        'reference' => $reference,
                        'created_by' => (string) $claim->user_id,
                        'reviewed_by_admin_id' => $admin?->id,
                    ]);

                    try {
                        $this->coinsService->reward(
                            $claim->user,
                            $coins,
                            $reference,
                            $claim->user_id
                        );
                    } catch (\Throwable $exception) {
                        Log::error('coin_claim.approve.ledger_insert_failed', [
                            'claim_id' => (string) $claim->id,
                            'user_id' => (string) $claim->user_id,
                            'amount' => $coins,
                            'created_by' => (string) $claim->user_id,
                            'reviewed_by_admin_id' => $admin?->id,
                            'error' => $exception->getMessage(),
                        ]);

                        throw $exception;
                    }
                }

                $claim = $claim->fresh('user');
                if ($claim->user) {
                    $claim->user->notify(new CoinClaimReviewedNotification(
                        claim: $claim,
                        status: 'approved',
                        coinsAwarded: $claim->coins_awarded !== null ? (int) $claim->coins_awarded : null,
                        reason: null,
                    ));
                }

                $this->emailService->sendApproved($claim);

                return 'Coin claim approved.';
            });

            return redirect()->back()->with('success', $message);
        } catch (\Throwable $exception) {
            Log::error('Coin claim approve failed', [
                'request_id' => $requestId,
                'id' => $id,
                'admin_id' => $admin?->id,
                'error' => $exception->getMessage(),
            ]);

            return redirect()->back()->with('error', $exception->getMessage());
        }
    }

    public function reject(string $id, RejectCoinClaimRequest $request): RedirectResponse
    {
        $requestId = (string) Str::uuid();
        Log::info('Coin claim reject requested', ['request_id' => $requestId, 'id' => $id]);

        $admin = Auth::guard('admin')->user();

        try {
            $message = DB::transaction(function () use ($id, $admin, $request) {
                $claim = CoinClaimRequest::with('user')->where('id', $id)->lockForUpdate()->firstOrFail();

                if (! AdminCircleScope::userInScope($admin, $claim->user_id)) {
                    abort(403);
                }

                if ($claim->status !== 'pending') {
                    return 'Claim already reviewed.';
                }

                $claim->status = 'rejected';
                $claim->reviewed_by_admin_id = $admin?->id;
                $claim->reviewed_at = now();
                $claim->admin_note = $request->validated('admin_note');
                $claim->save();

                $claim = $claim->fresh('user');
                if ($claim->user) {
                    $claim->user->notify(new CoinClaimReviewedNotification(
                        claim: $claim,
                        status: 'rejected',
                        coinsAwarded: null,
                        reason: $claim->admin_note,
                    ));
                }

                $this->emailService->sendRejected($claim);

                return 'Coin claim rejected.';
            });

            return redirect()->back()->with('success', $message);
        } catch (\Throwable $exception) {
            Log::error('Coin claim reject failed', [
                'request_id' => $requestId,
                'id' => $id,
                'admin_id' => $admin?->id,
                'error' => $exception->getMessage(),
            ]);

            return redirect()->back()->with('error', $exception->getMessage());
        }
    }
}

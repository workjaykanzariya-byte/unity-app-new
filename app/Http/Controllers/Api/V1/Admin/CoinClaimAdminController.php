<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\CoinClaims\RejectCoinClaimRequest;
use App\Http\Resources\CoinClaimRequestResource;
use App\Jobs\SendPushNotificationJob;
use App\Models\CoinClaimRequest;
use App\Models\CoinsLedger;
use App\Models\Notification;
use App\Models\User;
use App\Support\AdminCircleScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CoinClaimAdminController extends BaseApiController
{
    public function index(Request $request)
    {
        $admin = auth('admin')->user();

        $query = CoinClaimRequest::query()->with(['user:id,display_name,first_name,last_name,phone']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }
        if ($code = $request->query('activity_code')) {
            $query->where('activity_code', $code);
        }
        if ($from = $request->query('from_date')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('to_date')) {
            $query->whereDate('created_at', '<=', $to);
        }

        AdminCircleScope::applyToActivityQuery($query, $admin, 'coin_claim_requests.user_id', null);

        $paginator = $query->orderByDesc('created_at')->paginate(min((int) $request->query('per_page', 20), 100));

        return $this->success([
            'items' => CoinClaimRequestResource::collection($paginator),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(string $id)
    {
        $admin = auth('admin')->user();
        $claim = CoinClaimRequest::query()->with(['user:id,display_name,first_name,last_name,phone'])->findOrFail($id);

        if (! AdminCircleScope::userInScope($admin, $claim->user_id)) {
            abort(403);
        }

        return $this->success(new CoinClaimRequestResource($claim));
    }

    public function approve(Request $request, string $id)
    {
        $admin = auth('admin')->user();

        $claim = DB::transaction(function () use ($id, $admin) {
            $claim = CoinClaimRequest::query()->where('id', $id)->lockForUpdate()->firstOrFail();

            if (! AdminCircleScope::userInScope($admin, $claim->user_id)) {
                abort(403);
            }

            if ($claim->status !== 'pending') {
                abort(422, 'Only pending requests can be approved.');
            }

            $duplicate = CoinsLedger::query()
                ->where('reference', 'claim_coin:'.$claim->id)
                ->exists();

            if ($duplicate) {
                abort(422, 'Ledger already exists for this claim request.');
            }

            $coins = (int) config('coins.claim_coin.'.$claim->activity_code, 0);
            if ($coins <= 0) {
                abort(422, 'Coins mapping not configured for activity.');
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

            $title = 'Coins Added';
            $activityLabel = config('coins.claim_coin_labels.'.$claim->activity_code, $claim->activity_code);
            $body = "Your coin claim for {$activityLabel} has been approved. +{$coins} coins.";

            $notification = Notification::create([
                'user_id' => $claim->user_id,
                'type' => 'activity_update',
                'payload' => [
                    'notification_type' => 'coin_claim_approved',
                    'title' => $title,
                    'body' => $body,
                    'coin_claim_request_id' => (string) $claim->id,
                    'activity_code' => $claim->activity_code,
                    'coins_awarded' => $coins,
                ],
                'is_read' => false,
                'created_at' => now(),
            ]);

            SendPushNotificationJob::dispatch($user, $title, $body, [
                'type' => 'coin_claim_approved',
                'notification_id' => (string) $notification->id,
                'coin_claim_request_id' => (string) $claim->id,
                'activity_code' => $claim->activity_code,
                'coins_awarded' => $coins,
            ]);

            return $claim->refresh()->load('user:id,display_name,first_name,last_name,phone');
        });

        return $this->success(new CoinClaimRequestResource($claim), 'Coin claim approved successfully.');
    }

    public function reject(RejectCoinClaimRequest $request, string $id)
    {
        $admin = auth('admin')->user();
        $data = $request->validated();

        $claim = DB::transaction(function () use ($id, $admin, $data) {
            $claim = CoinClaimRequest::query()->where('id', $id)->lockForUpdate()->firstOrFail();

            if (! AdminCircleScope::userInScope($admin, $claim->user_id)) {
                abort(403);
            }

            if ($claim->status !== 'pending') {
                abort(422, 'Only pending requests can be rejected.');
            }

            $claim->update([
                'status' => 'rejected',
                'reviewed_by_admin_id' => $admin?->id,
                'reviewed_at' => now(),
                'admin_note' => $data['admin_note'] ?? null,
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
                        'admin_note' => $data['admin_note'] ?? null,
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

            return $claim->refresh()->load('user:id,display_name,first_name,last_name,phone');
        });

        return $this->success(new CoinClaimRequestResource($claim), 'Coin claim rejected successfully.');
    }
}

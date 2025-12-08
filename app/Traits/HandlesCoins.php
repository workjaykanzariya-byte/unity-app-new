<?php

namespace App\Traits;

use App\Models\User;
use App\Models\CoinsLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait HandlesCoins
{
    /**
     * Credit coins to a user for a specific activity.
     */
    protected function creditCoinsForActivity(
        User $user,
        string $activityId,
        string $reference,
        int $coins
    ): array {
        try {
            if ($coins <= 0) {
                return [
                    'coins_earned' => 0,
                    'total_coins' => $user->coins_balance,
                ];
            }

            return DB::transaction(function () use ($user, $activityId, $reference, $coins) {

                // Lock user
                $user = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

                // Calculate new balance
                $newBalance = (int)$user->coins_balance + (int)$coins;

                // Insert ledger entry
                CoinsLedger::create([
                    'transaction_id' => Str::uuid()->toString(),
                    'user_id'        => $user->id,
                    'amount'         => $coins,
                    'balance_after'  => $newBalance,
                    'activity_id'    => $activityId,
                    'reference'      => $reference,
                    'created_by'     => $user->id,
                    'created_at'     => now(),
                ]);

                // Update user balance
                $user->coins_balance = $newBalance;
                $user->save();

                return [
                    'coins_earned' => $coins,
                    'total_coins'  => $newBalance,
                ];
            });

        } catch (\Throwable $e) {
            Log::error('Coin credit failed', [
                'user_id'     => $user->id ?? null,
                'activity_id' => $activityId ?? null,
                'reference'   => $reference ?? null,
                'coins'       => $coins,
                'error'       => $e->getMessage(),
            ]);

            return [
                'coins_earned' => 0,
                'total_coins'  => $user->coins_balance ?? 0,
            ];
        }
    }
}

<?php

namespace App\Traits;

use App\Models\CoinsLedger;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait HandlesCoins
{
    protected function creditCoinsForActivity(
        User $user,
        string $activityId,
        string $reference,
        int $coins
    ): array {
        if ($coins <= 0) {
            return [
                'coins_earned' => 0,
                'total_coins' => $user->coins_balance,
            ];
        }

        try {
            return DB::transaction(function () use ($user, $activityId, $reference, $coins) {
                $lockedUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();
                $newBalance = (int) $lockedUser->coins_balance + (int) $coins;

                CoinsLedger::create([
                    'transaction_id' => Str::uuid()->toString(),
                    'user_id' => $lockedUser->id,
                    'amount' => $coins,
                    'balance_after' => $newBalance,
                    'activity_id' => $activityId,
                    'reference' => $reference,
                    'created_by' => $lockedUser->id,
                    'created_at' => now(),
                ]);

                $lockedUser->coins_balance = $newBalance;
                $lockedUser->save();

                return [
                    'coins_earned' => $coins,
                    'total_coins' => $newBalance,
                ];
            });
        } catch (\Throwable $e) {
            Log::error('Coin credit failed', [
                'user_id' => $user->id ?? null,
                'activity_id' => $activityId ?? null,
                'reference' => $reference ?? null,
                'coins' => $coins,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'coins_earned' => 0,
                'total_coins' => $user->coins_balance ?? 0,
            ];
        }
    }
}

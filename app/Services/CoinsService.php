<?php

namespace App\Services;

use App\Models\CoinsLedger;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CoinsService
{
    public function credit(
        string $userId,
        string $activityId,
        string $reference,
        int $coins
    ): array {
        try {
            return DB::transaction(function () use ($userId, $activityId, $reference, $coins) {
                $user = User::where('id', $userId)->lockForUpdate()->firstOrFail();

                $newBalance = (int) $user->coins_balance + $coins;

                CoinsLedger::create([
                    'transaction_id' => Str::uuid()->toString(),
                    'user_id' => $userId,
                    'amount' => $coins,
                    'balance_after' => $newBalance,
                    'activity_id' => $activityId,
                    'reference' => $reference,
                    'created_by' => $userId,
                    'created_at' => now(),
                ]);

                $user->coins_balance = $newBalance;
                $user->save();

                return [
                    'coins_earned' => $coins,
                    'total_coins' => $newBalance,
                ];
            });
        } catch (Throwable $e) {
            Log::error('Coins credit failed', [
                'user_id' => $userId,
                'activity_id' => $activityId,
                'reference' => $reference,
                'coins' => $coins,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

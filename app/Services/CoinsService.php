<?php

namespace App\Services;

use App\Models\CoinsLedger;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CoinsService
{
    public function credit(
        string $userId,
        string $activityId,
        string $reference,
        int $coins
    ): array {
        return DB::transaction(function () use ($userId, $activityId, $reference, $coins) {
            $user = User::where('id', $userId)->lockForUpdate()->firstOrFail();

            $currentBalance = (int) $user->coins_balance;
            $newBalance = $currentBalance + $coins;

            $ledger = new CoinsLedger([
                'user_id' => $userId,
                'amount' => $coins,
                'balance_after' => $newBalance,
                'activity_id' => $activityId,
                'reference' => $reference,
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            $ledger->transaction_id = (string) Str::uuid();
            $ledger->save();

            $user->coins_balance = $newBalance;
            $user->save();

            return [
                'coins_earned' => $coins,
                'total_coins' => $newBalance,
            ];
        });
    }
}

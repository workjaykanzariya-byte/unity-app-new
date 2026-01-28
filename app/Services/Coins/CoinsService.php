<?php

namespace App\Services\Coins;

use App\Models\CoinsLedger;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CoinsService
{
    public function rewardForActivity(
        User $user,
        string $activityType,
        $activityId = null,
        ?string $reference = null,
        ?string $createdBy = null
    ): ?CoinsLedger {
        $amount = config('coins.activity_rewards')[$activityType] ?? 0;

        if ($amount === 0) {
            return null;
        }

        return DB::transaction(function () use ($user, $activityType, $activityId, $reference, $createdBy, $amount) {
            $user = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

            $newBalance = $user->coins_balance + $amount;

            $user->update([
                'coins_balance' => $newBalance,
            ]);

            $ledgerData = [
                'transaction_id' => Str::uuid()->toString(),
                'user_id' => $user->id,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reference' => $reference ?? ucfirst(str_replace('_', ' ', $activityType)) . ' reward',
                'created_by' => $createdBy ?? $user->id,
                'created_at' => now(),
            ];

            return CoinsLedger::create($ledgerData);
        });
    }

    public function reward(User $user, int $amount, string $reasonLabel, array $meta = []): ?CoinsLedger
    {
        if ($amount <= 0) {
            return null;
        }

        $reference = $meta['reference'] ?? $reasonLabel;
        $createdBy = $meta['created_by'] ?? null;

        return DB::transaction(function () use ($user, $amount, $reference, $createdBy) {
            $user = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

            $newBalance = $user->coins_balance + $amount;

            $user->update([
                'coins_balance' => $newBalance,
            ]);

            $ledgerData = [
                'transaction_id' => Str::uuid()->toString(),
                'user_id' => $user->id,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reference' => $reference,
                'created_by' => $createdBy ?? $user->id,
                'created_at' => now(),
            ];

            return CoinsLedger::create($ledgerData);
        });
    }
}

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

            return CoinsLedger::create([
                'transaction_id' => Str::uuid()->toString(),
                'user_id' => $user->id,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'activity_id' => $activityId,
                'reference' => $reference ?? ucfirst(str_replace('_', ' ', $activityType)) . ' reward',
                'created_by' => $createdBy ?? $user->id,
                'created_at' => now(),
            ]);
        });
    }
}

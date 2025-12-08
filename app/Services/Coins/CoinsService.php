<?php

namespace App\Services\Coins;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CoinsService
{
    /**
     * Award coins for a specific activity and return the new balance.
     */
    public function awardForActivity(User $user, string $activityType, string $activityId): int
    {
        $map = [
            'p2p-meetings' => 10,
            'requirements' => 5,
            'referrals' => 15,
            'business-deals' => 25,
            'testimonials' => 5,
        ];

        if (! isset($map[$activityType])) {
            return (int) $user->coins_balance;
        }

        $coinsToAdd = $map[$activityType];

        return DB::transaction(function () use ($user, $activityType, $activityId, $coinsToAdd) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

            $currentBalance = (int) $lockedUser->coins_balance;
            $newBalance = $currentBalance + $coinsToAdd;

            DB::table('coins_ledger')->insert([
                'transaction_id' => (string) Str::uuid(),
                'user_id' => $lockedUser->id,
                'amount' => $coinsToAdd,
                'balance_after' => $newBalance,
                'activity_id' => $activityId,
                'reference' => $activityType,
                'created_by' => $lockedUser->id,
                'created_at' => now(),
            ]);

            $lockedUser->coins_balance = $newBalance;
            $lockedUser->save();

            return $newBalance;
        });
    }
}

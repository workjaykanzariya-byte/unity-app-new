<?php

namespace App\Services\Referrals;

use App\Models\CoinsLedger;
use App\Models\ReferralHistory;
use App\Models\User;
use App\Services\Coins\CoinsService;
use App\Services\Notifications\NotifyUserService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ReferralService
{
    public function __construct(
        private readonly ReferralCodeService $referralCodeService,
        private readonly CoinsService $coinsService,
        private readonly NotifyUserService $notifyUserService,
    ) {
    }

    public function ensureReferralCode(User $user, bool $regenerate = false): string
    {
        if (! $regenerate && ! empty($user->referral_code)) {
            return (string) $user->referral_code;
        }

        $user->referral_code = $this->referralCodeService->generateUniqueCodeForUser($user);
        $user->save();

        return (string) $user->referral_code;
    }

    public function buildReferralProfile(User $user): array
    {
        $code = $this->ensureReferralCode($user);

        $totalReferrals = User::query()->where('referred_by_user_id', $user->id)->count();
        $totalCoins = (int) ReferralHistory::query()
            ->where('referrer_user_id', $user->id)
            ->sum('reward_coins');

        return [
            'referral_code' => $code,
            'referral_link' => $this->referralCodeService->buildReferralLink($code),
            'total_referrals' => $totalReferrals,
            'total_referral_coins' => $totalCoins,
        ];
    }

    public function getStats(User $user): array
    {
        $profile = $this->buildReferralProfile($user);

        $successful = ReferralHistory::query()
            ->where('referrer_user_id', $user->id)
            ->where('reward_status', 'granted')
            ->count();

        return array_merge($profile, [
            'my_referral_code' => $profile['referral_code'],
            'my_referral_link' => $profile['referral_link'],
            'successful_referrals_count' => $successful,
        ]);
    }

    public function validateReferralCode(string $code): ?User
    {
        $normalizedCode = strtoupper(trim($code));
        $referrer = User::query()
            ->select(['id', 'first_name', 'last_name', 'display_name', 'email', 'referral_code'])
            ->where('referral_code', $normalizedCode)
            ->first();

        Log::info('referral.code.validated', [
            'referral_code' => $normalizedCode,
            'valid' => $referrer !== null,
            'referrer_user_id' => $referrer?->id,
        ]);

        return $referrer;
    }

    public function validateReferralCodeOrFail(?string $code): ?User
    {
        if (blank($code)) {
            return null;
        }

        $referrer = $this->validateReferralCode($code);

        if (! $referrer) {
            throw ValidationException::withMessages([
                'referral_code' => ['The selected referral code is invalid.'],
            ]);
        }

        return $referrer;
    }

    public function processRegistrationReferral(User $newUser, ?string $referralCode): ?array
    {
        if (blank($referralCode)) {
            return null;
        }

        $normalizedCode = strtoupper(trim((string) $referralCode));
        $referrer = $this->validateReferralCodeOrFail($normalizedCode);

        if (! $referrer || (string) $referrer->id === (string) $newUser->id) {
            throw ValidationException::withMessages([
                'referral_code' => ['A user cannot refer themselves.'],
            ]);
        }

        return DB::transaction(function () use ($newUser, $referrer, $normalizedCode) {
            $newUser = User::query()->where('id', $newUser->id)->lockForUpdate()->firstOrFail();

            if (! empty($newUser->referred_by_user_id)) {
                Log::warning('referral.registration.duplicate_prevented', [
                    'referred_user_id' => (string) $newUser->id,
                    'existing_referrer_user_id' => (string) $newUser->referred_by_user_id,
                ]);

                return null;
            }

            $newUser->forceFill([
                'referred_by_user_id' => $referrer->id,
                'referred_at' => now(),
                'referral_code_used' => $normalizedCode,
            ])->save();

            Log::info('referral.registration.linked', [
                'referred_user_id' => (string) $newUser->id,
                'referrer_user_id' => (string) $referrer->id,
                'referral_code' => $normalizedCode,
            ]);

            $history = ReferralHistory::query()->firstOrCreate(
                ['referred_user_id' => $newUser->id],
                [
                    'referrer_user_id' => $referrer->id,
                    'referral_code' => $normalizedCode,
                    'reward_coins' => 0,
                    'reward_status' => 'pending',
                    'source' => 'registration',
                ]
            );

            $rewardCoins = (int) config('coins.activity_rewards.referral_signup', 100);
            $reference = 'referral_signup:' . $newUser->id;

            $existingReward = CoinsLedger::query()
                ->where('user_id', $referrer->id)
                ->where('reference', $reference)
                ->first();

            if (! $existingReward && $rewardCoins > 0) {
                $ledger = $this->coinsService->reward($referrer, $rewardCoins, $reference, $newUser->id);

                $history->forceFill([
                    'reward_coins' => (int) ($ledger?->amount ?? $rewardCoins),
                    'reward_status' => 'granted',
                ])->save();

                Log::info('referral.reward.granted', [
                    'referrer_user_id' => (string) $referrer->id,
                    'referred_user_id' => (string) $newUser->id,
                    'coins' => (int) ($ledger?->amount ?? $rewardCoins),
                ]);
            } else {
                $history->forceFill([
                    'reward_status' => 'granted',
                ])->save();

                Log::warning('referral.reward.duplicate_prevented', [
                    'referrer_user_id' => (string) $referrer->id,
                    'referred_user_id' => (string) $newUser->id,
                    'reference' => $reference,
                ]);
            }

            $this->notifyUserService->notifyUser(
                $referrer,
                $newUser,
                'referral_signup',
                [
                    'title' => 'New Referral Joined',
                    'body' => 'A new peer has joined using your referral code.',
                    'referred_user_id' => (string) $newUser->id,
                    'referred_user_name' => trim((string) $newUser->display_name),
                    'referral_code_used' => $normalizedCode,
                ],
                $history
            );

            Log::info('referral.notification.triggered', [
                'referrer_user_id' => (string) $referrer->id,
                'referred_user_id' => (string) $newUser->id,
            ]);

            return [
                'referrer_user_id' => (string) $referrer->id,
                'referrer_name' => trim((string) ($referrer->display_name ?: ($referrer->first_name . ' ' . $referrer->last_name))),
                'referrer_email' => (string) $referrer->email,
                'referral_code_used' => $normalizedCode,
                'reward_coins' => (int) ($history->reward_coins ?? 0),
            ];
        });
    }

    public function members(User $user, int $perPage = 20): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));

        return User::query()
            ->where('referred_by_user_id', $user->id)
            ->with(['referralHistoryAsReferred'])
            ->orderByDesc('referred_at')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function validateCodeResponse(string $code): array
    {
        $referrer = $this->validateReferralCode($code);

        return [
            'valid' => $referrer !== null,
            'referrer_name' => $referrer
                ? trim((string) ($referrer->display_name ?: ($referrer->first_name . ' ' . $referrer->last_name)))
                : null,
        ];
    }
}

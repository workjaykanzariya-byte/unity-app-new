<?php

namespace App\Services\Referrals;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ReferralCodeService
{
    public function sanitizeNamePrefix(string $name, int $maxLength = 6): string
    {
        $clean = strtoupper((string) preg_replace('/[^A-Za-z]/', '', $name));
        $trimmed = substr($clean, 0, max(1, min($maxLength, 10)));

        return $trimmed !== '' ? $trimmed : 'PEER';
    }

    public function generateUniqueCodeForUser(User $user): string
    {
        $name = trim((string) ($user->display_name ?: ($user->first_name . ' ' . $user->last_name)));
        $prefix = $this->sanitizeNamePrefix($name, 6);

        $attempts = 0;
        do {
            $attempts++;
            $code = $prefix . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $exists = User::query()->where('referral_code', $code)->exists();
        } while ($exists && $attempts < 25);

        if ($exists) {
            $code = 'PEER' . strtoupper(Str::random(2)) . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        }

        Log::info('referral.code.generated', [
            'user_id' => (string) $user->id,
            'referral_code' => $code,
            'attempts' => $attempts,
        ]);

        return $code;
    }

    public function buildReferralLink(string $code): string
    {
        $base = (string) config('referrals.register_url', rtrim((string) config('app.url'), '/') . '/register');
        $param = (string) config('referrals.query_param', 'ref');

        return rtrim($base, '?&') . '?' . http_build_query([$param => $code]);
    }
}


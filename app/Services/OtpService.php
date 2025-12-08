<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    /**
     * @return array{model: OtpCode, plain_code: string}
     */
    public function generateOtp(User $user, string $purpose, int $ttlMinutes = 15): array
    {
        $this->invalidateAll($purpose, $user);

        $otp = (string) random_int(1000, 9999);

        $otpCode = OtpCode::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'purpose' => $purpose,
            'code' => Hash::make($otp),
            'expires_at' => now()->addMinutes($ttlMinutes),
            'used_at' => null,
        ]);

        return [
            'model' => $otpCode,
            'plain_code' => $otp,
        ];
    }

    public function verifyOtp(User $user, string $purpose, string $plainOtp): bool
    {
        $otpCode = OtpCode::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->first();

        if (! $otpCode) {
            return false;
        }

        if (! Hash::check($plainOtp, $otpCode->code)) {
            return false;
        }

        $otpCode->used_at = now();
        $otpCode->save();

        return true;
    }

    public function invalidateAll(string $purpose, User $user): void
    {
        OtpCode::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);
    }
}

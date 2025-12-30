<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Mail\AdminOtpMail;
use App\Models\AdminLoginOtp;
use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AdminAuthApiController extends Controller
{
    public function requestOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower($validated['email']);

        $adminUser = AdminUser::where('email', $email)
            ->where('is_active', true)
            ->first();

        if (! $adminUser) {
            return response()->json(['success' => true]);
        }

        $existingOtp = AdminLoginOtp::where('email', $email)->first();

        if ($existingOtp && $existingOtp->last_sent_at && now()->diffInSeconds($existingOtp->last_sent_at) < 60) {
            return response()->json([
                'message' => 'Please wait before requesting another OTP.',
            ], 429);
        }

        $otp = (string) random_int(100000, 999999);

        AdminLoginOtp::updateOrCreate(
            ['email' => $email],
            [
                'otp_hash' => Hash::make($otp),
                'expires_at' => now()->addMinutes(10),
                'last_sent_at' => now(),
                'attempts' => 0,
            ],
        );

        Mail::to($email)->send(new AdminOtpMail($otp, 10));

        return response()->json(['success' => true]);
    }

    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
        ]);

        $email = strtolower($validated['email']);
        $otpInput = $validated['otp'];

        $adminUser = AdminUser::where('email', $email)
            ->where('is_active', true)
            ->first();

        if (! $adminUser) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid OTP.'],
            ]);
        }

        $otpRecord = AdminLoginOtp::where('email', $email)->first();

        if (! $otpRecord) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid OTP.'],
            ]);
        }

        if (now()->greaterThan($otpRecord->expires_at)) {
            $otpRecord->delete();

            throw ValidationException::withMessages([
                'otp' => ['The OTP has expired.'],
            ]);
        }

        if ($otpRecord->attempts >= 5) {
            return response()->json([
                'message' => 'Too many attempts. Please request a new OTP.',
            ], 423);
        }

        if (! Hash::check($otpInput, $otpRecord->otp_hash)) {
            $otpRecord->increment('attempts');

            throw ValidationException::withMessages([
                'otp' => ['Invalid OTP.'],
            ]);
        }

        $otpRecord->delete();

        Auth::guard('admin')->login($adminUser);
        $adminUser->forceFill(['last_login_at' => now()])->save();
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'admin' => [
                'id' => $adminUser->id,
                'email' => $adminUser->email,
                'name' => $adminUser->name,
            ],
        ]);
    }

    public function me()
    {
        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();

            return response()->json([
                'admin' => [
                    'id' => $admin->id,
                    'email' => $admin->email,
                    'name' => $admin->name,
                ],
            ]);
        }

        return response()->json(['message' => 'Unauthenticated.'], 401);
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['success' => true]);
    }
}

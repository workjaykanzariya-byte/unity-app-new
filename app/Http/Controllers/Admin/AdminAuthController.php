<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminLoginOtpMail;
use App\Models\AdminLoginOtp;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    private const ADMIN_ROLES = [
        'global_admin',
        'industry_director',
        'ded',
        'moderator',
        'finance_admin',
    ];

    private const REQUEST_LIMIT = 5;
    private const VERIFY_LIMIT = 5;

    public function showLogin(Request $request): View
    {
        $email = $request->old('email') ?? $request->session()->get('login_email');

        return view('admin.auth.login', [
            'email' => $email,
        ]);
    }

    public function requestOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = $validated['email'];
        $cacheKey = 'admin_otp_request:' . Str::lower($email);
        $requestAttempts = Cache::get($cacheKey, 0);

        if ($requestAttempts >= self::REQUEST_LIMIT) {
            return back()->withErrors([
                'email' => 'Too many OTP requests. Please try again later.',
            ])->withInput();
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return back()->withErrors([
                'email' => 'We could not find an account with that email.',
            ])->withInput();
        }

        Cache::put($cacheKey, $requestAttempts + 1, 300);

        $otp = (string) random_int(1000, 9999);

        AdminLoginOtp::updateOrCreate(
            ['email' => $email],
            [
                'otp_hash' => Hash::make($otp),
                'expires_at' => now()->addMinutes(5),
                'used_at' => null,
                'last_sent_at' => now(),
            ]
        );

        Mail::to($email)->send(new AdminLoginOtpMail($otp));
        Log::info("ADMIN OTP for {$email}: {$otp}");

        return back()
            ->with('status', 'OTP sent to your email.')
            ->with('otp_requested', true)
            ->with('login_email', $email);
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:4'],
        ]);

        $email = $validated['email'];
        $otp = $validated['otp'];

        $cacheKey = 'admin_otp_verify:' . Str::lower($email);
        $verifyAttempts = Cache::get($cacheKey, 0);

        if ($verifyAttempts >= self::VERIFY_LIMIT) {
            return back()->withErrors([
                'otp' => 'Too many verification attempts. Please try again later.',
            ])->withInput();
        }

        $otpRecord = AdminLoginOtp::where('email', $email)
            ->whereNull('used_at')
            ->orderByDesc('expires_at')
            ->first();

        if (! $otpRecord) {
            Cache::put($cacheKey, $verifyAttempts + 1, 300);

            return back()->withErrors([
                'otp' => 'OTP not requested.',
            ])->withInput();
        }

        if ($otpRecord->expires_at->isPast()) {
            Cache::put($cacheKey, $verifyAttempts + 1, 300);

            return back()->withErrors([
                'otp' => 'OTP expired.',
            ])->withInput();
        }

        if (! Hash::check($otp, $otpRecord->otp_hash)) {
            Cache::put($cacheKey, $verifyAttempts + 1, 300);

            return back()->withErrors([
                'otp' => 'Invalid OTP.',
            ])->withInput();
        }

        $otpRecord->forceFill([
            'used_at' => now(),
        ])->save();

        $user = User::where('email', $email)->first();

        if (! $user || ! in_array($user->role, self::ADMIN_ROLES, true)) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'You are not an admin',
            ]);
        }

        Cache::forget($cacheKey);

        $request->session()->invalidate();
        $request->session()->regenerate();
        $request->session()->put('admin_user_id', $user->id);
        $request->session()->put('admin_role', $user->role);
        $request->session()->put('login_email', $email);

        return redirect('/admin/dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget([
            'admin_user_id',
            'admin_role',
            'login_email',
        ]);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }
}

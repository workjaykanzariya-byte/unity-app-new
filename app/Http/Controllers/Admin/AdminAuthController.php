<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminLoginOtpMail;
use App\Models\AdminLoginOtp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function showEmailForm(Request $request): View|RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login_email');
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = $data['email'];

        $user = User::where('email', $email)->first();

        if (! $user || ! $user->adminRoles()->whereIn('key', config('admin.allowed_role_keys', []))->exists()) {
            return back()->withErrors([
                'email' => 'You are not admin',
            ]);
        }

        $otpRecord = AdminLoginOtp::firstOrNew(['email' => $email]);
        $now = Carbon::now();

        if ($otpRecord->last_sent_at && $otpRecord->last_sent_at->diffInSeconds($now) < 60) {
            return back()->withErrors([
                'email' => 'OTP already sent recently. Please check your email.',
            ]);
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $otpRecord->otp_hash = Hash::make($otp);
        $otpRecord->expires_at = $now->copy()->addMinutes(10);
        $otpRecord->last_sent_at = $now;
        $otpRecord->attempts = 0;
        $otpRecord->save();

        Mail::to($email)->send(new AdminLoginOtpMail($otp));

        return redirect()->route('admin.login.verify.form', ['email' => $email])
            ->with('status', 'OTP sent to your email.');
    }

    public function showVerifyForm(Request $request): View|RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login_verify', [
            'email' => $request->query('email'),
        ]);
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
        ]);

        $otpRecord = AdminLoginOtp::where('email', $data['email'])->first();

        if (! $otpRecord) {
            return back()->withErrors([
                'otp' => 'Invalid OTP',
            ]);
        }

        $now = Carbon::now();

        if ($otpRecord->attempts >= 5 && $otpRecord->expires_at && $otpRecord->expires_at->isFuture()) {
            return back()->withErrors([
                'otp' => 'Too many attempts. Please request a new OTP.',
            ]);
        }

        if ($otpRecord->expires_at && $otpRecord->expires_at->isPast()) {
            $otpRecord->delete();

            return back()->withErrors([
                'otp' => 'OTP expired. Please request a new one.',
            ]);
        }

        if (! Hash::check($data['otp'], $otpRecord->otp_hash)) {
            $otpRecord->attempts = ($otpRecord->attempts ?? 0) + 1;
            $otpRecord->save();

            return back()->withErrors([
                'otp' => 'Invalid OTP',
            ]);
        }

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! $user->adminRoles()->whereIn('key', config('admin.allowed_role_keys', []))->exists()) {
            $otpRecord->delete();

            return back()->withErrors([
                'email' => 'You are not admin',
            ]);
        }

        Auth::guard('admin')->login($user);
        $request->session()->regenerate();

        $otpRecord->delete();

        return redirect()->route('admin.dashboard')->with('status', 'Logged in successfully.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login.form')->with('status', 'Logged out successfully.');
    }
}

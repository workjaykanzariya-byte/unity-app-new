<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLoginOtp;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.auth.login');
    }

    public function showVerifyForm(Request $request)
    {
        return view('admin.auth.verify', ['email' => $request->query('email')]);
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $now = now(config('app.timezone'));
        $otp = AdminLoginOtp::firstOrNew(['email' => $request->input('email')]);

        if ($otp->last_sent_at && $otp->last_sent_at->diffInSeconds($now) < 60) {
            return back()->withErrors(['email' => 'OTP already sent recently. Please wait before retrying.']);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $otp->fill([
            'otp_hash' => Hash::make($code),
            'expires_at' => $now->copy()->addMinutes(10),
            'last_sent_at' => $now,
            'attempts' => 0,
        ]);

        if (! $otp->id) {
            $otp->id = Str::uuid()->toString();
        }

        $otp->save();

        // TODO: replace with real mailer
        Log::info('Admin OTP generated', ['email' => $otp->email, 'code' => $code]);

        return redirect()->route('admin.login.verify_form', ['email' => $otp->email])
            ->with('status', 'OTP sent to your email.');
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'string'],
        ]);

        $now = now(config('app.timezone'));

        $otp = AdminLoginOtp::where('email', $request->input('email'))->first();

        if (! $otp) {
            return back()->withErrors(['otp' => 'OTP not found.']);
        }

        $expiresAt = Carbon::parse($otp->expires_at, config('app.timezone'));

        Log::info('Admin OTP verify', [
            'now' => $now->toDateTimeString(),
            'expires_at_raw' => $otp->expires_at,
            'expires_at_parsed' => $expiresAt->toDateTimeString(),
            'app_tz' => config('app.timezone'),
        ]);

        if ($expiresAt->lte($now)) {
            return back()->withErrors(['otp' => 'OTP expired. Please request a new one.']);
        }

        if (! Hash::check($request->input('otp'), $otp->otp_hash)) {
            $otp->increment('attempts');

            if ($otp->attempts >= 5) {
                return back()->withErrors(['otp' => 'Too many attempts. Request a new OTP.']);
            }

            return back()->withErrors(['otp' => 'Invalid OTP.']);
        }

        $otp->delete();

        $user = User::where('email', $otp->email)->first();

        if (! $user) {
            return back()->withErrors(['email' => 'Admin user not found.']);
        }

        Auth::guard('admin')->login($user);

        return redirect()->route('admin.dashboard');
    }
}

<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminLoginOtp;
use App\Models\AdminUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function showLogin(Request $request): RedirectResponse|View
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function requestOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        $email = strtolower($request->input('email'));
        $adminUser = $this->eligibleAdmin($email);

        if (! $adminUser) {
            return response()->json([
                'message' => 'You are not admin',
            ], 403);
        }

        $existingOtp = AdminLoginOtp::query()
            ->where('email', $email)
            ->orderByDesc('last_sent_at')
            ->first();

        if ($existingOtp && $existingOtp->last_sent_at && $existingOtp->last_sent_at->gt(now()->subSeconds(30))) {
            return response()->json([
                'message' => 'Please wait before requesting another OTP.',
            ], 429);
        }

        $otp = (string) random_int(1000, 9999);
        $otpRecord = null;

        DB::transaction(function () use (&$otpRecord, $existingOtp, $email, $otp): void {
            $otpRecord = $existingOtp ?? new AdminLoginOtp();

            if (! $existingOtp) {
                $otpRecord->id = (string) Str::uuid();
            }

            $otpRecord->email = $email;
            $otpRecord->otp_hash = Hash::make($otp);
            $otpRecord->expires_at = now()->addMinutes(5);
            $otpRecord->last_sent_at = now();
            $otpRecord->attempts = 0;
            $otpRecord->save();
        });

        Mail::raw(
            "Your admin login OTP is {$otp}. It expires in 5 minutes.",
            static function ($message) use ($email): void {
                $message->to($email)->subject('Your Admin Login OTP');
            }
        );

        return response()->json([
            'message' => 'OTP sent to your email.',
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:4'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        $email = strtolower($request->input('email'));
        $otp = $request->input('otp');

        $otpRecord = AdminLoginOtp::query()
            ->where('email', $email)
            ->orderByDesc('last_sent_at')
            ->first();

        if (! $otpRecord) {
            return response()->json([
                'message' => 'OTP expired',
            ], 410);
        }

        if ($otpRecord->expires_at && $otpRecord->expires_at->isPast()) {
            return response()->json([
                'message' => 'OTP expired',
            ], 410);
        }

        if ($otpRecord->attempts >= 5) {
            return response()->json([
                'message' => 'Too many attempts',
            ], 423);
        }

        $adminUser = $this->eligibleAdmin($email);

        if (! $adminUser) {
            return response()->json([
                'message' => 'You are not admin',
            ], 403);
        }

        if (! Hash::check($otp, $otpRecord->otp_hash)) {
            $otpRecord->increment('attempts');

            return response()->json([
                'message' => 'Invalid OTP',
            ], 422);
        }

        DB::transaction(function () use ($otpRecord): void {
            $otpRecord->attempts = 0;
            $otpRecord->expires_at = now();
            $otpRecord->save();
        });

        Auth::guard('admin')->login($adminUser);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'OTP verified',
            'redirect' => route('admin.dashboard'),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    private function eligibleAdmin(string $email): ?AdminUser
    {
        return AdminUser::query()
            ->where('email', $email)
            ->whereHas('roles', static function ($query): void {
                $query->where('key', 'global_admin');
            })
            ->first();
    }
}

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
            ->orderByDesc('created_at')
            ->first();

        $now = now('UTC');
        if ($existingOtp && $existingOtp->last_sent_at && $existingOtp->last_sent_at->diffInSeconds($now) < 30) {
            return response()->json([
                'message' => 'Please wait before requesting another OTP.',
            ], 429);
        }

        $otp = (string) random_int(1000, 9999);
        $expiresAt = $now->copy()->addMinutes(5);
        $otpRecord = null;

        DB::transaction(function () use (&$otpRecord, $email, $otp, $expiresAt, $now): void {
            $otpRecord = AdminLoginOtp::create([
                'id' => (string) Str::uuid(),
                'email' => $email,
                'otp_hash' => Hash::make($otp),
                'expires_at' => $expiresAt,
                'last_sent_at' => $now,
                'attempts' => 0,
                'used_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
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

        $adminUser = $this->eligibleAdmin($email);

        if (! $adminUser) {
            return response()->json([
                'message' => 'You are not admin',
            ], 403);
        }

        $result = DB::transaction(function () use ($email, $otp): array {
            $otpRecord = AdminLoginOtp::query()
                ->where('email', $email)
                ->whereNull('used_at')
                ->orderByDesc('created_at')
                ->lockForUpdate()
                ->first();

            if (! $otpRecord) {
                return ['status' => 410, 'message' => 'OTP expired'];
            }

            if ($otpRecord->attempts >= 5) {
                return ['status' => 423, 'message' => 'Too many attempts'];
            }

            $now = now('UTC');

            if (! $otpRecord->expires_at || $otpRecord->expires_at->lt($now)) {
                return ['status' => 410, 'message' => 'OTP expired'];
            }

            if (! Hash::check($otp, $otpRecord->otp_hash)) {
                $otpRecord->attempts += 1;
                $otpRecord->save();

                return ['status' => 422, 'message' => 'Invalid OTP'];
            }

            $otpRecord->used_at = $now;
            $otpRecord->save();

            return ['status' => 200, 'message' => 'OTP verified'];
        });

        if ($result['status'] !== 200) {
            return response()->json([
                'message' => $result['message'],
            ], $result['status']);
        }

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
            ->whereExists(function ($query) {
                $query->selectRaw(1)
                    ->from('admin_user_roles')
                    ->join('roles', 'roles.id', '=', 'admin_user_roles.role_id')
                    ->whereColumn('admin_user_roles.user_id', 'admin_users.id')
                    ->where('roles.key', 'global_admin');
            })
            ->first();
    }
}

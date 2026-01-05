<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Mail\AdminLoginOtpMail;
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
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
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
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = strtolower($validator->validated()['email']);
        $adminUser = $this->resolveEligibleAdmin($email);

        if (! $adminUser) {
            return response()->json([
                'success' => false,
                'message' => 'You are not admin',
            ], 403);
        }

        $existingOtp = AdminLoginOtp::where('email', $email)->first();
        if ($existingOtp && $existingOtp->last_sent_at && $existingOtp->last_sent_at->greaterThan(now()->subSeconds(30))) {
            return response()->json([
                'success' => false,
                'message' => 'Please wait before requesting a new OTP.',
            ], 429);
        }

        $otp = (string) random_int(1000, 9999);

        AdminLoginOtp::updateOrCreate(
            ['email' => $email],
            [
                'otp_hash' => Hash::make($otp),
                'expires_at' => now()->addMinutes(5),
                'last_sent_at' => now(),
                'attempts' => 0,
            ]
        );

        Mail::to($email)->send(new AdminLoginOtpMail($otp, $adminUser));

        return response()->json([
            'success' => true,
            'message' => 'OTP sent',
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
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $email = strtolower($data['email']);
        $otp = $data['otp'];

        $adminUser = $this->resolveEligibleAdmin($email);
        if (! $adminUser) {
            return response()->json([
                'success' => false,
                'message' => 'You are not admin',
            ], 403);
        }

        $otpRecord = AdminLoginOtp::where('email', $email)->first();

        if (! $otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP',
            ], 400);
        }

        if ($otpRecord->expires_at && $otpRecord->expires_at->isPast()) {
            $otpRecord->delete();

            return response()->json([
                'success' => false,
                'message' => 'OTP expired',
            ], 410);
        }

        if ($otpRecord->attempts >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'Too many attempts',
            ], 423);
        }

        if (! Hash::check($otp, $otpRecord->otp_hash)) {
            $otpRecord->increment('attempts');

            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP',
            ], 400);
        }

        $otpRecord->delete();

        Auth::guard('admin')->login($adminUser);
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'redirect' => url('/admin'),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    private function resolveEligibleAdmin(string $email): ?AdminUser
    {
        $adminUser = AdminUser::where('email', $email)->first();

        if (! $adminUser) {
            return null;
        }

        $allowedRoleKeys = config('admin.allowed_role_keys', []);

        $hasRole = DB::table('admin_user_roles')
            ->join('roles', 'roles.id', '=', 'admin_user_roles.role_id')
            ->where('admin_user_roles.admin_user_id', $adminUser->id)
            ->when(! empty($allowedRoleKeys), function ($query) use ($allowedRoleKeys) {
                $query->whereIn('roles.key', $allowedRoleKeys);
            })
            ->exists();

        return $hasRole ? $adminUser : null;
    }
}

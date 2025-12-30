<?php

namespace App\Http\Controllers\Api\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Mail\AdminOtpMail;
use App\Models\AdminLoginOtp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AdminAuthController extends Controller
{
    public function requestOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower($validated['email']);
        $adminUser = $this->getAdminUserOrFail($email);

        $existingOtp = AdminLoginOtp::where('email', $email)->first();
        if ($existingOtp && $existingOtp->last_sent_at && $existingOtp->last_sent_at->gt(now()->subSeconds(60))) {
            return response()->json([
                'message' => 'Please wait before requesting another OTP.',
            ], 429);
        }

        $otp = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        AdminLoginOtp::updateOrCreate(
            ['email' => $email],
            [
                'otp_hash' => Hash::make($otp),
                'expires_at' => now()->addMinutes(10),
                'last_sent_at' => now(),
                'attempts' => 0,
            ]
        );

        Mail::to($adminUser->email)->send(new AdminOtpMail($otp));

        return response()->json([
            'message' => 'OTP sent to your email address.',
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:4'],
        ]);

        $email = strtolower($validated['email']);
        $otpInput = $validated['otp'];

        $adminUser = $this->getAdminUserOrFail($email);
        $otpRecord = AdminLoginOtp::where('email', $email)->first();

        if (!$otpRecord) {
            return response()->json([
                'message' => 'OTP not found. Please request a new one.',
            ], 404);
        }

        if ($otpRecord->expires_at && $otpRecord->expires_at->isPast()) {
            $otpRecord->delete();

            return response()->json([
                'message' => 'OTP expired. Please request a new one.',
            ], 422);
        }

        if ($otpRecord->attempts >= 5) {
            return response()->json([
                'message' => 'Too many attempts. Please request a new OTP.',
            ], 429);
        }

        if (!Hash::check($otpInput, $otpRecord->otp_hash)) {
            $otpRecord->increment('attempts');

            return response()->json([
                'message' => 'Invalid OTP. Please try again.',
            ], 422);
        }

        $otpRecord->delete();

        $roles = $this->getAdminRoles($adminUser->id);
        $token = $adminUser->createToken('admin-panel', ['admin'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'admin' => $this->formatAdmin($adminUser, $roles),
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->tokenCan('admin')) {
            throw new AccessDeniedHttpException('Admin access required');
        }

        $roles = $this->getAdminRoles($user->id);

        return response()->json([
            'admin' => $this->formatAdmin($user, $roles),
        ]);
    }

    public function logout(Request $request)
    {
        $token = $request->user()?->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    private function getAdminUserOrFail(string $email): User
    {
        $user = User::where('email', $email)->first();

        if (!$user || !DB::table('admin_user_roles')->where('user_id', $user->id)->exists()) {
            throw new AccessDeniedHttpException('Not authorized for admin panel.');
        }

        return $user;
    }

    /**
     * @return array<int, string>
     */
    private function getAdminRoles(string $userId): array
    {
        return DB::table('admin_user_roles')
            ->where('user_id', $userId)
            ->get()
            ->map(function ($role): string {
                if (isset($role->role_key)) {
                    return (string) $role->role_key;
                }

                if (isset($role->role)) {
                    return (string) $role->role;
                }

                if (isset($role->name)) {
                    return (string) $role->name;
                }

                return 'admin';
            })
            ->values()
            ->all();
    }

    private function formatAdmin(User $user, array $roles): array
    {
        return [
            'id' => $user->id,
            'name' => $user->display_name ?? trim($user->first_name . ' ' . ($user->last_name ?? '')),
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'roles' => $roles,
        ];
    }
}

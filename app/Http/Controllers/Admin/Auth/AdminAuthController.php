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

    public function requestOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower(trim($validated['email']));
        $adminUser = $this->eligibleAdmin($email);

        if (! $adminUser) {
            return back()
                ->withInput(['email' => $email])
                ->withErrors(['email' => 'You are not admin']);
        }

        $recentOtp = AdminLoginOtp::query()
            ->where('email', $email)
            ->orderByDesc('created_at')
            ->first();

        if ($recentOtp && $recentOtp->last_sent_at && $recentOtp->last_sent_at->diffInSeconds(now('UTC')) < 30) {
            return back()
                ->withInput(['email' => $email])
                ->withErrors(['email' => 'Please wait before requesting another OTP.']);
        }

        $otp = (string) random_int(1000, 9999);
        $now = now('UTC');
        $expiresAt = $now->copy()->addMinutes(5);

        DB::table('admin_login_otps')->updateOrInsert(
            ['email' => $email],
            [
                'id' => (string) Str::uuid(),
                'otp_hash' => Hash::make($otp),
                'expires_at' => $expiresAt,
                'last_sent_at' => $now,
                'attempts' => 0,
                'used_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        Mail::raw(
            "Your admin login OTP is {$otp}. It expires in 5 minutes.",
            static function ($message) use ($email): void {
                $message->to($email)->subject('Your Admin Login OTP');
            }
        );

        $request->session()->forget('errors');
        $request->session()->put('admin_login_email', $email);

        return redirect()
            ->route('admin.login')
            ->withInput(['email' => $email])
            ->with('otp_sent', true)
            ->with('status', 'OTP sent');
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:4'],
        ]);

        $email = strtolower(trim($validated['email']));
        $otp = trim($validated['otp']);

        $adminUser = $this->eligibleAdmin($email);

        if (! $adminUser) {
            return back()->withErrors(['email' => 'You are not admin']);
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

            $now = now('UTC');

            if ($otpRecord->used_at || ($otpRecord->expires_at && $otpRecord->expires_at->lt($now))) {
                return ['status' => 410, 'message' => 'OTP expired'];
            }

            if ($otpRecord->attempts >= 5) {
                return ['status' => 423, 'message' => 'Too many attempts'];
            }

            if (! Hash::check($otp, $otpRecord->otp_hash)) {
                $otpRecord->attempts += 1;
                $otpRecord->updated_at = $now;
                $otpRecord->save();

                return ['status' => 422, 'message' => 'Invalid OTP'];
            }

            $otpRecord->used_at = $now;
            $otpRecord->updated_at = $now;
            $otpRecord->attempts += 1;
            $otpRecord->save();

            return ['status' => 200, 'message' => 'OTP verified'];
        });

        if ($result['status'] !== 200) {
            return back()
                ->withInput(['email' => $email])
                ->withErrors(['otp' => $result['message']]);
        }

        Auth::guard('admin')->login($adminUser);
        $request->session()->put('admin_user_id', $adminUser->id);
        $request->session()->put('admin_login_email', $adminUser->email);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->forget(['admin_user_id', 'admin_login_email']);
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

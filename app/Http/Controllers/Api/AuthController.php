<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\RequestOtpRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends BaseApiController
{
    /**
     * Register a new user and return user + token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Create new user (no findOrFail here)
        $user = new User();
        $user->email             = $data['email'];
        $user->phone             = $data['phone']        ?? null;
        $user->first_name        = $data['first_name'];
        $user->last_name         = $data['last_name']    ?? null;
        $user->city_id           = $data['city_id']      ?? null;
        $user->password_hash     = Hash::make($data['password']);
        $user->display_name      = $data['display_name']
            ?? trim($user->first_name . ' ' . ($user->last_name ?? ''));
        $user->membership_status = 'visitor';
        $user->coins_balance     = 0;
        $user->save();

        // Ensure Postgres-generated UUID is loaded
        $user->refresh();

        // Create Sanctum token
        $token = $user->createToken('api')->plainTextToken;

        // SAFETY: if anything in resource/loading throws ModelNotFound,
        // we still respond successfully.
        try {
            $userResource = new UserResource($user->load('city'));
        } catch (ModelNotFoundException $e) {
            $userResource = new UserResource($user);
        }

        // We bypass $this->success() here to avoid any hidden logic
        // that might query User again and explode.
        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'data'    => [
                'user'  => $userResource,
                'token' => $token,
            ],
        ], 201);
    }

    /**
     * Login with email + password.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password_hash)) {
            return $this->error('Invalid credentials', 401);
        }

        if ($user->membership_status === 'suspended') {
            return $this->error('Account is suspended', 403);
        }

        $user->last_login_at = now();
        $user->save();
        $user->refresh();

        $token = $user->createToken('api')->plainTextToken;

        return $this->success(
            [
                'user'  => new UserResource($user->load('city')),
                'token' => $token,
            ],
            'Login successful'
        );
    }

    /**
     * Request OTP for email login.
     */
    public function requestOtp(RequestOtpRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if ($user) {
            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            Cache::put('otp_' . $request->email, $otp, now()->addMinutes(10));

            Mail::raw("Your Peers Global Unity login OTP is: {$otp}", function ($message) use ($request) {
                $message->to($request->email)->subject('Your Login OTP');
            });

            EmailLog::create([
                'to_email'     => $request->email,
                'template_key' => 'auth_otp',
                'payload'      => ['otp' => $otp],
                'status'       => 'sent',
                'sent_at'      => now(),
            ]);
        }

        // Always return success (don’t leak if email exists)
        return $this->success(null, 'If your email is registered, an OTP has been sent.');
    }

    /**
     * Verify OTP and issue token.
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $cachedOtp = Cache::get('otp_' . $request->email);

        if (empty($cachedOtp) || $cachedOtp !== $request->otp) {
            return $this->error('Invalid or expired OTP', 400);
        }

        Cache::forget('otp_' . $request->email);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return $this->error('User not found', 404);
        }

        if ($user->membership_status === 'suspended') {
            return $this->error('Account is suspended', 403);
        }

        $user->refresh();

        $token = $user->createToken('api')->plainTextToken;

        return $this->success(
            [
                'user'  => new UserResource($user->load('city')),
                'token' => $token,
            ],
            'OTP verified'
        );
    }

    /**
     * Forgot password – send reset link.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if ($user) {
            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                [
                    'token'      => Hash::make($token),
                    'created_at' => now(),
                ]
            );

            $frontendUrl = Config::get('app.frontend_url', 'https://app.peersglobal.com');
            $resetUrl    = $frontendUrl . '/?reset_token=' . $token . '&email=' . $request->email;

            Mail::raw("Reset your password using this link: {$resetUrl}", function ($message) use ($request) {
                $message->to($request->email)->subject('Reset your password');
            });

            EmailLog::create([
                'to_email'     => $request->email,
                'template_key' => 'forgot_password',
                'payload'      => ['reset_url' => $resetUrl],
                'status'       => 'sent',
                'sent_at'      => now(),
            ]);
        }

        return $this->success(null, 'If your email is registered, a reset link has been sent.');
    }

    /**
     * Reset password using token from email.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (! $record) {
            return $this->error('Invalid password reset token', 400);
        }

        if (! Hash::check($request->token, $record->token)) {
            return $this->error('Invalid password reset token', 400);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return $this->error('User not found', 404);
        }

        $user->password_hash = Hash::make($request->password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return $this->success(null, 'Password reset successful');
    }

    /**
     * Logout current device.
     */
    public function logout(): JsonResponse
    {
        $user = Auth::guard('sanctum')->user();
        $user?->currentAccessToken()?->delete();

        return $this->success(null, 'Logged out successfully');
    }

    /**
     * Return current authenticated user.
     */
    public function me(): JsonResponse
    {
        $user = Auth::guard('sanctum')->user();

        return $this->success(new UserResource($user->load('city')));
    }
}

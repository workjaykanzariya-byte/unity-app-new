<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\BaseApiController;
use App\Mail\OtpMail;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PasswordResetOtpController extends BaseApiController
{
    public function __construct(private readonly OtpService $otpService)
    {
    }

    public function sendOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if ($user) {
            $generated = $this->otpService->generateOtp($user, 'password_reset');

            Mail::to($user->email)->send(new OtpMail(
                $generated['plain_code'],
                'Your password reset OTP',
                'Use this OTP to reset your Peers Global Unity password.'
            ));
        }

        return $this->success(null, 'If your email is registered, an OTP has been sent.');
    }

    public function resetWithOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:4'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or OTP.',
                'data' => null,
            ], 422);
        }

        if (! $this->otpService->verifyOtp($user, 'password_reset', $data['otp'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP.',
                'data' => null,
            ], 422);
        }

        $hashedPassword = Hash::make($data['password']);
        $user->password = $hashedPassword;
        $user->password_hash = $hashedPassword;
        $user->save();

        $this->otpService->invalidateAll('password_reset', $user);

        return $this->success(null, 'Password reset successfully.');
    }
}

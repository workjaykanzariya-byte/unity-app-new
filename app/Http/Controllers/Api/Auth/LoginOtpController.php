<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\UserResource;
use App\Mail\OtpMail;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class LoginOtpController extends BaseApiController
{
    public function __construct(private readonly OtpService $otpService)
    {
    }

    public function requestOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found with this email.',
                'data' => null,
            ], 422);
        }

        $generated = $this->otpService->generateOtp($user, 'login');

        Mail::to($user->email)->send(new OtpMail(
            $generated['plain_code'],
            'Your login OTP',
            'Use this OTP to login to Peers Global Unity.'
        ));

        return $this->success(null, 'OTP sent to your email.');
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:4'],
            'device_name' => ['nullable', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or OTP.',
                'data' => null,
            ], 422);
        }

        if (! $this->otpService->verifyOtp($user, 'login', $data['otp'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP.',
                'data' => null,
            ], 422);
        }

        if ($user->membership_status === 'suspended') {
            return response()->json([
                'success' => false,
                'message' => 'Account is suspended.',
                'data' => null,
            ], 403);
        }

        $token = $user->createToken($data['device_name'] ?? 'mobile')->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user->load('city')),
        ], 'Login successful.');
    }
}

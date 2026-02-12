<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\UserPushToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class PushTokenController extends BaseApiController
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'platform' => ['required', 'string', 'in:android,ios,web'],
            'device_id' => ['nullable', 'string'],
        ]);

        try {
            $user = $request->user();

            UserPushToken::where('token', $validated['token'])
                ->where('platform', $validated['platform'])
                ->where('user_id', '!=', $user->id)
                ->delete();

            $pushToken = UserPushToken::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'token' => $validated['token'],
                ],
                [
                    'platform' => $validated['platform'],
                    'device_id' => $validated['device_id'] ?? null,
                    'last_seen_at' => now(),
                ]
            );

            return $this->success([
                'id' => $pushToken->id,
                'token' => $pushToken->token,
                'platform' => $pushToken->platform,
                'device_id' => $pushToken->device_id,
                'last_seen_at' => $pushToken->last_seen_at,
            ], 'Push token saved successfully');
        } catch (Throwable $throwable) {
            report($throwable);

            return $this->error('Unable to save push token', 500);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        try {
            $deleted = UserPushToken::where('user_id', $request->user()->id)
                ->where('token', $validated['token'])
                ->delete();

            return $this->success([
                'deleted' => $deleted > 0,
            ], 'Push token deleted successfully');
        } catch (Throwable $throwable) {
            report($throwable);

            return $this->error('Unable to delete push token', 500);
        }
    }
}

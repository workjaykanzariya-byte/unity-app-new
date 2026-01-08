<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use App\Models\Chat;

Broadcast::channel('user.{userId}', function ($user, $userId) {
    Log::info('Broadcast auth user channel', [
        'auth_user_id' => $user?->id,
        'param_user_id' => $userId,
        'result' => (string) ($user?->id) === (string) $userId,
    ]);

    return (string) ($user?->id) === (string) $userId;
});

Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    $ok = Chat::where('id', $chatId)
        ->where(function ($q) use ($user) {
            $q->where('user1_id', $user->id)
              ->orWhere('user2_id', $user->id);
        })
        ->exists();

    Log::info('Broadcast auth chat channel', [
        'auth_user_id' => $user?->id,
        'chat_id' => $chatId,
        'result' => $ok,
    ]);

    return $ok;
});

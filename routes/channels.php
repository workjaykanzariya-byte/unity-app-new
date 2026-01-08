<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use App\Models\Chat;

/**
 * NOTE:
 * - In JS you subscribe to: echo.private(`user.${userId}`)  => channel name here is: user.{userId}
 * - In JS you subscribe to: echo.private(`chat.${chatId}`)  => channel name here is: chat.{chatId}
 * Echo will automatically prefix private channels as "private-" when calling /broadcasting/auth.
 */

/**
 * User private channel authorization
 * Only the same user can join their own user.{userId} channel.
 */
Broadcast::channel('user.{userId}', function ($user, $userId) {
    $authUserId = (string) ($user?->id ?? '');
    $paramUserId = (string) $userId;

    $ok = ($authUserId !== '') && ($authUserId === $paramUserId);

    Log::info('Broadcast auth user channel', [
        'auth_user_id' => $authUserId,
        'param_user_id' => $paramUserId,
        'result' => $ok,
    ]);

    return $ok;
});

/**
 * Chat private channel authorization
 * Only chat participants can join chat.{chatId}.
 */
Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    $authUserId = (string) ($user?->id ?? '');
    $chatId = (string) $chatId;

    // If no authenticated user, deny
    if ($authUserId === '') {
        Log::warning('Broadcast auth chat channel denied - no auth user', [
            'chat_id' => $chatId,
        ]);
        return false;
    }

    $ok = Chat::query()
        ->where('id', $chatId)
        ->where(function ($q) use ($authUserId) {
            $q->where('user1_id', $authUserId)
              ->orWhere('user2_id', $authUserId);
        })
        ->exists();

    Log::info('Broadcast auth chat channel', [
        'auth_user_id' => $authUserId,
        'chat_id' => $chatId,
        'result' => $ok,
    ]);

    return $ok;
});

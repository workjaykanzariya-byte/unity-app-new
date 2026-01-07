<?php

use App\Models\Chat;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    return Chat::where('id', $chatId)
        ->where(function ($q) use ($user) {
            $q->where('user1_id', $user->id)
              ->orWhere('user2_id', $user->id);
        })
        ->exists();
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (string) $user->id === (string) $userId;
});

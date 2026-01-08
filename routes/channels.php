<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Chat;

Broadcast::channel('user.{userId}', function ($user, string $userId) {
    return (string) $user->id === (string) $userId;
});

Broadcast::channel('chat.{chatId}', function ($user, string $chatId) {
    return Chat::where('id', $chatId)
        ->where(function ($query) use ($user) {
            $query->where('user1_id', $user->id)
                ->orWhere('user2_id', $user->id);
        })
        ->exists();
});

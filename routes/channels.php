<?php

use App\Models\Chat;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('private-chat.{chatId}', function ($user, string $chatId) {
    return Chat::where('id', $chatId)
        ->where(function ($q) use ($user) {
            $q->where('user1_id', $user->id)
                ->orWhere('user2_id', $user->id);
        })
        ->exists();
});

Broadcast::channel('presence-chat.{chatId}', function ($user, string $chatId) {
    $isMember = Chat::where('id', $chatId)
        ->where(function ($q) use ($user) {
            $q->where('user1_id', $user->id)
                ->orWhere('user2_id', $user->id);
        })
        ->exists();

    if (! $isMember) {
        return false;
    }

    return [
        'id' => (string) $user->id,
        'display_name' => $user->display_name
            ?? trim(($user->first_name ?? '').' '.($user->last_name ?? '')),
    ];
});

Broadcast::channel('App.Models.User.{id}', function ($user, string $id) {
    return (string) $user->id === (string) $id;
});

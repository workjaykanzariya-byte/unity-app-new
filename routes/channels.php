<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('private-chat.{chatId}', function ($user, string $chatId) {
    return ! empty($user?->id);
});

Broadcast::channel('presence-chat.{chatId}', function ($user, string $chatId) {
    return [
        'id' => (string) $user->id,
        'display_name' => $user->display_name
            ?? trim(($user->first_name ?? '').' '.($user->last_name ?? '')),
    ];
});

Broadcast::channel('App.Models.User.{id}', function ($user, string $id) {
    return (string) $user->id === (string) $id;
});

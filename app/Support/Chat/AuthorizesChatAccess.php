<?php

namespace App\Support\Chat;

use App\Models\Chat;
use App\Models\User;

trait AuthorizesChatAccess
{
    protected function canAccessChat(User $user, Chat $chat): bool
    {
        return $chat->canAccessChat($user);
    }
}

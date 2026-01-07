<?php

namespace App\Support;

class NotificationTypes
{
    public const CHAT_MESSAGE = 'new_message';

    public static function normalize(string $type): string
    {
        if ($type === 'chat_message') {
            return self::CHAT_MESSAGE;
        }

        return $type;
    }
}

<?php

namespace App\Observers;

use App\Events\Chat\MessageDelivered;
use App\Models\Message;

class MessageObserver
{
    public function created(Message $message): void
    {
        $message->loadMissing('sender');

        broadcast(new MessageDelivered($message))->toOthers();
    }
}

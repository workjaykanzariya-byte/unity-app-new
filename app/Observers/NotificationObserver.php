<?php

namespace App\Observers;

use App\Events\Notification\NotificationPushed;
use App\Models\Notification;

class NotificationObserver
{
    public function created(Notification $notification): void
    {
        broadcast(new NotificationPushed($notification));
    }
}

<?php

namespace App\Providers;

use App\Events\ActivityCreated;
use App\Listeners\Reverb\MarkUserOffline;
use App\Listeners\Reverb\MarkUserOnline;
use App\Listeners\SendActivityEmails;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ActivityCreated::class => [
            SendActivityEmails::class,
        ],
        'Laravel\\Reverb\\Events\\ConnectionOpened' => [
            MarkUserOnline::class,
        ],
        'Laravel\\Reverb\\Events\\ConnectionClosed' => [
            MarkUserOffline::class,
        ],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

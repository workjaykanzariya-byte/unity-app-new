<?php

namespace App\Providers;

use App\Events\ActivityCreated;
use App\Listeners\SendActivityEmails;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ActivityCreated::class => [
            SendActivityEmails::class,
        ],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

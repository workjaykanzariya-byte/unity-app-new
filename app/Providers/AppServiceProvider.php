<?php

namespace App\Providers;

use App\Models\Message;
use App\Models\Notification;
use App\Observers\MessageObserver;
use App\Observers\NotificationObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Message::observe(MessageObserver::class);
        Notification::observe(NotificationObserver::class);
    }
}

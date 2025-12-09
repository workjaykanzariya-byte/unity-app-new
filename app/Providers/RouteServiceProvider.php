<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        // Ensures {member} must be a valid UUID
        Route::pattern('member', '[0-9a-fA-F\-]{36}');
    }
}

<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    protected function redirectTo($request)
    {
        // ✅ For any API request, never redirect to route('login')
        if ($request->expectsJson() || $request->is('api/*') || $request->routeIs('api.*')) {
            return null;
        }

        // This project doesn't rely on web login redirects here
        return null;
    }
}

<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    protected function redirectTo($request)
    {
        // ✅ IMPORTANT: For API/JSON requests, NEVER redirect to route('login')
        // Returning null makes Laravel return 401/403 JSON instead of throwing "Route [login] not defined."
        if ($request->expectsJson()) {
            return null;
        }

        // If this project does not use a web login route, keep null to avoid exceptions.
        return null;
    }
}

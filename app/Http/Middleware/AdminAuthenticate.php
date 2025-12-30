<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::guard('admin')->check()) {
            if ($request->expectsJson() || $request->is('admin/api/*')) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->to('/admin/login');
        }

        return $next($request);
    }
}

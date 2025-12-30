<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EnsureAdminToken
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !$user->currentAccessToken() || !$user->tokenCan('admin')) {
            throw new AccessDeniedHttpException('Admin access required');
        }

        return $next($request);
    }
}

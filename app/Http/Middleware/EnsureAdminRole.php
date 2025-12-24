<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $user = Auth::guard('admin')->user();

        if (! $user) {
            return redirect()->route('admin.login.form');
        }

        $allowedRoles = config('admin.allowed_role_keys', []);
        $hasRole = $user->adminRoles()->whereIn('key', $allowedRoles)->exists();

        if (! $hasRole) {
            Auth::guard('admin')->logout();

            return redirect()->route('admin.login.form')->withErrors([
                'email' => 'You are not admin',
            ]);
        }

        return $next($request);
    }
}

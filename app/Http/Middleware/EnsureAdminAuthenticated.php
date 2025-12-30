<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAuthenticated
{
    private const ADMIN_ROLES = [
        'global_admin',
        'industry_director',
        'ded',
        'moderator',
        'finance_admin',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $adminUserId = $request->session()->get('admin_user_id');
        $adminRole = $request->session()->get('admin_role');

        if (! $adminUserId || ! $adminRole) {
            return redirect('/admin/login');
        }

        if (! in_array($adminRole, self::ADMIN_ROLES, true)) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, 'You are not an admin');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Support\AdminAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminCircleScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();

        if (! $admin) {
            return redirect()->route('admin.login');
        }

        if (AdminAccess::isSuper($admin)) {
            return $next($request);
        }

        if (AdminAccess::isCircleScoped($admin)) {
            $allowedCircleIds = AdminAccess::allowedCircleIds($admin);
            $request->attributes->set('allowed_circle_ids', $allowedCircleIds);

            return $next($request);
        }

        $request->attributes->set('allowed_circle_ids', []);

        return $next($request);
    }
}

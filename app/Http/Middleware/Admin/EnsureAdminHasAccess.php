<?php

namespace App\Http\Middleware\Admin;

use App\Services\Admin\CircleAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminHasAccess
{
    public function __construct(private readonly CircleAccessService $circleAccess)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $adminUser = auth('admin')->user();

        if (! $adminUser) {
            return $next($request);
        }

        if ($this->circleAccess->isGlobalAdmin($adminUser)) {
            view()->share('is_global_admin', true);

            return $next($request);
        }

        if (! $this->circleAccess->isCircleLeader($adminUser)) {
            return response()
                ->view('admin.errors.no-access', [], 403);
        }

        $allowedCircleIds = $this->circleAccess->allowedCircleIds($adminUser);

        $request->attributes->set('allowed_circle_ids', $allowedCircleIds);
        view()->share('allowed_circle_ids', $allowedCircleIds);
        view()->share('is_global_admin', false);

        return $next($request);
    }
}

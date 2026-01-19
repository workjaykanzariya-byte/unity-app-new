<?php

namespace App\Http\Middleware\Admin;

use App\Services\Admin\CircleAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminCircleAccess
{
    public function __construct(private readonly CircleAccessService $circleAccess)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $adminUser = $this->circleAccess->currentAdmin();

        if (! $adminUser) {
            return $next($request);
        }

        if ($this->circleAccess->isGlobalAdmin($adminUser)) {
            view()->share('is_global_admin', true);
            view()->share('is_circle_leader', false);

            return $next($request);
        }

        if (! $this->circleAccess->isCircleLeader($adminUser)) {
            return response()->view('admin.errors.no-access', [], 403);
        }

        $allowedCircleIds = $this->circleAccess->allowedCircleIds($adminUser);
        $allowedMemberIds = $this->circleAccess->allowedMemberIds($adminUser);

        $request->attributes->set('allowed_circle_ids', $allowedCircleIds);
        $request->attributes->set('allowed_member_ids', $allowedMemberIds);

        view()->share('allowed_circle_ids', $allowedCircleIds);
        view()->share('allowed_member_ids', $allowedMemberIds);
        view()->share('is_global_admin', false);
        view()->share('is_circle_leader', true);

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdminRoleMiddleware
{
    private const CORE_ADMIN_ROLES = [
        'global_admin',
        'industry_director',
        'ded',
        'circle_leader',
    ];

    public function handle(Request $request, Closure $next, string ...$allowedRoles): Response
    {
        $admin = Auth::guard('admin')->user();

        if (! $admin) {
            return redirect()->route('admin.login');
        }

        $admin->loadMissing('roles:id,key');
        $adminRoleKeys = $admin->roles->pluck('key')->all();

        $requiredRoles = $this->normalizedRoles($allowedRoles);
        $roleCheckKeys = $requiredRoles ?: self::CORE_ADMIN_ROLES;
        $missingRoles = $this->missingRoleKeys($roleCheckKeys);

        if ($missingRoles) {
            Log::error('Required admin role keys missing from roles table.', [
                'missing_keys' => $missingRoles,
            ]);

            return response()
                ->view('admin.errors.roles-missing', [
                    'missingRoles' => $missingRoles,
                ], 500);
        }

        if (empty($requiredRoles)) {
            return $next($request);
        }

        if (in_array('global_admin', $adminRoleKeys, true)) {
            return $next($request);
        }

        if (! array_intersect($requiredRoles, $adminRoleKeys)) {
            return response()
                ->view('admin.errors.forbidden', [
                    'message' => 'You do not have permission to access this section.',
                ], 403);
        }

        return $next($request);
    }

    private function normalizedRoles(array $roles): array
    {
        return array_values(array_filter(array_map('trim', $roles)));
    }

    private function missingRoleKeys(array $roles): array
    {
        if (empty($roles)) {
            return [];
        }

        $existing = Role::query()
            ->whereIn('key', $roles)
            ->pluck('key')
            ->all();

        return array_values(array_diff($roles, $existing));
    }
}

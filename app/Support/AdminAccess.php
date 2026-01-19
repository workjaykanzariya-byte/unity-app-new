<?php

namespace App\Support;

use App\Models\AdminUser;
use App\Models\CircleMember;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class AdminAccess
{
    private const CACHE_TTL = 300;

    private const SUPER_ROLE_KEYS = [
        'global_admin',
        'industry_director',
        'ded',
    ];

    private const CIRCLE_SCOPED_KEYS = [
        'circle_leader',
        'chair',
        'vice_chair',
        'secretary',
        'founder',
        'director',
        'member',
    ];

    public static function resolveAppUser(?AdminUser $admin): ?User
    {
        if (! $admin) {
            return null;
        }

        $cacheKey = 'admin-access:user:' . $admin->id;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($admin) {
            $email = trim(strtolower((string) $admin->email));
            if ($email === '') {
                return null;
            }

            return User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();
        });
    }

    public static function adminRoleKeys(?AdminUser $admin): array
    {
        if (! $admin) {
            return [];
        }

        $cacheKey = 'admin-access:roles:' . $admin->id;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($admin) {
            return Role::query()
                ->join('admin_user_roles', 'admin_user_roles.role_id', '=', 'roles.id')
                ->where('admin_user_roles.user_id', $admin->id)
                ->pluck('roles.key')
                ->unique()
                ->values()
                ->all();
        });
    }

    public static function isSuper(?AdminUser $admin): bool
    {
        $roleKeys = self::adminRoleKeys($admin);

        return (bool) array_intersect(self::SUPER_ROLE_KEYS, $roleKeys);
    }

    public static function isCircleScoped(?AdminUser $admin): bool
    {
        if (! $admin || self::isSuper($admin)) {
            return false;
        }

        $roleKeys = self::adminRoleKeys($admin);

        return (bool) array_intersect(self::CIRCLE_SCOPED_KEYS, $roleKeys);
    }

    public static function allowedCircleIds(?AdminUser $admin): array
    {
        if (! $admin) {
            return [];
        }

        $cacheKey = 'admin-access:circles:' . $admin->id;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($admin) {
            $user = self::resolveAppUser($admin);
            if (! $user) {
                return [];
            }

            return CircleMember::query()
                ->where('user_id', $user->id)
                ->pluck('circle_id')
                ->unique()
                ->values()
                ->all();
        });
    }
}

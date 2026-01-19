<?php

namespace App\Services\Admin;

use App\Models\AdminUser;
use App\Models\CircleMember;

class CircleAccessService
{
    private const LEADER_ROLES = [
        'founder',
        'chair',
        'vice_chair',
        'secretary',
        'circle_director',
    ];

    public function isGlobalAdmin(?AdminUser $adminUser): bool
    {
        if (! $adminUser) {
            return false;
        }

        return $adminUser->roles()
            ->where('key', 'global_admin')
            ->exists();
    }

    public function isCircleLeader(?AdminUser $adminUser): bool
    {
        if (! $adminUser) {
            return false;
        }

        return CircleMember::query()
            ->where('user_id', $adminUser->id)
            ->whereIn('role', self::LEADER_ROLES)
            ->exists();
    }

    public function allowedCircleIds(?AdminUser $adminUser): array
    {
        if (! $adminUser) {
            return [];
        }

        return CircleMember::query()
            ->where('user_id', $adminUser->id)
            ->pluck('circle_id')
            ->all();
    }
}

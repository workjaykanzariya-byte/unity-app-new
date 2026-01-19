<?php

namespace App\Services\Admin;

use App\Models\AdminUser;
use App\Models\CircleMember;
use Illuminate\Support\Facades\Schema;

class CircleAccessService
{
    private const LEADER_ROLES = [
        'founder',
        'chair',
        'vice_chair',
        'secretary',
        'circle_director',
    ];

    public function currentAdmin(): ?AdminUser
    {
        return auth('admin')->user();
    }

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

        $memberColumn = $this->memberIdColumn();

        return CircleMember::query()
            ->where($memberColumn, $adminUser->id)
            ->whereIn('role', self::LEADER_ROLES)
            ->exists();
    }

    public function allowedCircleIds(?AdminUser $adminUser): array
    {
        if (! $adminUser) {
            return [];
        }

        $memberColumn = $this->memberIdColumn();

        return CircleMember::query()
            ->where($memberColumn, $adminUser->id)
            ->distinct()
            ->pluck('circle_id')
            ->all();
    }

    public function allowedMemberIds(?AdminUser $adminUser): array
    {
        if (! $adminUser) {
            return [];
        }

        $memberColumn = $this->memberIdColumn();
        $circleIds = $this->allowedCircleIds($adminUser);

        if ($circleIds === []) {
            return [];
        }

        return CircleMember::query()
            ->whereIn('circle_id', $circleIds)
            ->distinct()
            ->pluck($memberColumn)
            ->all();
    }

    private function memberIdColumn(): string
    {
        return Schema::hasColumn('circle_members', 'member_id')
            ? 'member_id'
            : 'user_id';
    }
}

<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\AdminUser;
use App\Services\Admin\CircleAccessService;

trait AppliesCircleScope
{
    protected function currentAdmin(): ?AdminUser
    {
        return auth('admin')->user();
    }

    protected function isGlobalAdmin(): bool
    {
        $circleAccess = app(CircleAccessService::class);

        return $circleAccess->isGlobalAdmin($circleAccess->currentAdmin());
    }

    protected function allowedCircleIds(): array
    {
        $request = request();

        if ($request->attributes->has('allowed_circle_ids')) {
            return (array) $request->attributes->get('allowed_circle_ids', []);
        }

        $circleAccess = app(CircleAccessService::class);

        return $circleAccess->allowedCircleIds($circleAccess->currentAdmin());
    }

    protected function allowedMemberIds(): array
    {
        $request = request();

        if ($request->attributes->has('allowed_member_ids')) {
            return (array) $request->attributes->get('allowed_member_ids', []);
        }

        $circleAccess = app(CircleAccessService::class);

        return $circleAccess->allowedMemberIds($circleAccess->currentAdmin());
    }

    protected function scopeUsersQuery($query)
    {
        if ($this->isGlobalAdmin()) {
            return $query;
        }

        $table = $query->getModel()->getTable();

        return $query->whereIn("{$table}.id", $this->allowedMemberIds());
    }

    protected function scopeActivitiesQuery($query, string $memberColumn)
    {
        if ($this->isGlobalAdmin()) {
            return $query;
        }

        return $query->whereIn($memberColumn, $this->allowedMemberIds());
    }

    protected function scopeCoinsQuery($query, string $memberColumn = 'user_id')
    {
        if ($this->isGlobalAdmin()) {
            return $query;
        }

        return $query->whereIn($memberColumn, $this->allowedMemberIds());
    }
}

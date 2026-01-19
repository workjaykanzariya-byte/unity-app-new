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
        return app(CircleAccessService::class)->isGlobalAdmin($this->currentAdmin());
    }

    protected function allowedCircleIds(): array
    {
        $request = request();

        if ($request->attributes->has('allowed_circle_ids')) {
            return (array) $request->attributes->get('allowed_circle_ids', []);
        }

        return app(CircleAccessService::class)->allowedCircleIds($this->currentAdmin());
    }

    protected function applyCircleScopeToUsersQuery($query)
    {
        if ($this->isGlobalAdmin()) {
            return $query;
        }

        $allowedCircleIds = $this->allowedCircleIds();
        $table = $query->getModel()->getTable();

        return $query->whereIn("{$table}.id", function ($sub) use ($allowedCircleIds) {
            $sub->select('user_id')
                ->from('circle_members')
                ->whereIn('circle_id', $allowedCircleIds);
        });
    }

    protected function applyCircleScopeToActivitiesQuery($query, string $userColumn = 'user_id')
    {
        if ($this->isGlobalAdmin()) {
            return $query;
        }

        $allowedCircleIds = $this->allowedCircleIds();

        return $query->whereIn($userColumn, function ($sub) use ($allowedCircleIds) {
            $sub->select('user_id')
                ->from('circle_members')
                ->whereIn('circle_id', $allowedCircleIds);
        });
    }

    protected function applyCircleScopeToCoinsQuery($query, string $userColumn = 'user_id')
    {
        if ($this->isGlobalAdmin()) {
            return $query;
        }

        $allowedCircleIds = $this->allowedCircleIds();

        return $query->whereIn($userColumn, function ($sub) use ($allowedCircleIds) {
            $sub->select('user_id')
                ->from('circle_members')
                ->whereIn('circle_id', $allowedCircleIds);
        });
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PublicMemberProfileResource;
use App\Models\User;

class PublicMemberProfileController extends BaseApiController
{
    public function show(string $slug)
    {
        $member = User::query()
            ->with([
                'city:id,name',
                'activeCircle:id,name',
                'circleMembers' => function ($query) {
                    $query->select(['id', 'user_id', 'circle_id', 'role', 'status', 'deleted_at'])
                        ->whereNull('deleted_at')
                        ->with('circle:id,name');
                },
            ])
            ->where(function ($query) use ($slug): void {
                $query->where('public_profile_slug', $slug);

                if ($this->looksLikeUuid($slug)) {
                    $query->orWhere('id', $slug);
                }
            })
            ->first();

        if (! $member) {
            return $this->error('Member not found.', 404);
        }

        return $this->success(new PublicMemberProfileResource($member));
    }

    private function looksLikeUuid(string $value): bool
    {
        return (bool) preg_match(
            '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/',
            $value
        );
    }
}

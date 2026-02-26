<?php

namespace App\Services\Collaboration;

use App\Models\CollaborationPost;
use App\Models\CollaborationType;
use App\Models\Industry;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class CollaborationPostService
{
    public function createForUser(User $user, array $data): CollaborationPost
    {
        if ($user->isFreeMember()) {
            $activePostCount = CollaborationPost::query()
                ->where('user_id', $user->id)
                ->where('status', CollaborationPost::STATUS_ACTIVE)
                ->where('expires_at', '>=', now())
                ->count();

            if ($activePostCount >= 2) {
                throw ValidationException::withMessages([
                    'collaborations' => ['Free members can have maximum 2 active collaboration posts. Please upgrade to post more.'],
                ]);
            }
        }

        $industryId = (string) Arr::get($data, 'industry_id');
        $hasChildren = Industry::query()->active()->where('parent_id', $industryId)->exists();

        if ($hasChildren) {
            throw ValidationException::withMessages([
                'industry_id' => ['Please select a leaf industry.'],
            ]);
        }

        $type = CollaborationType::query()
            ->where('id', $data['collaboration_type_id'])
            ->firstOrFail();

        return CollaborationPost::query()->create([
            'user_id' => $user->id,
            'collaboration_type_id' => $type->id,
            'collaboration_type' => $type->slug ?? $type->name,
            'title' => $data['title'],
            'description' => $data['description'],
            'scope' => $data['scope'],
            'countries_of_interest' => $data['countries_of_interest'] ?? null,
            'preferred_model' => $data['preferred_model'] ?? null,
            'industry_id' => $data['industry_id'],
            'business_stage' => $data['business_stage'],
            'years_in_operation' => $data['years_in_operation'],
            'urgency' => $data['urgency'],
            'status' => CollaborationPost::STATUS_ACTIVE,
            'posted_at' => now(),
            'expires_at' => now()->addDays(60),
        ]);
    }
}

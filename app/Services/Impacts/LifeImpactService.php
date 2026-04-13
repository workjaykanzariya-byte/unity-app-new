<?php

namespace App\Services\Impacts;

use App\Models\Impact;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LifeImpactService
{
    public function __construct(
        private readonly LifeImpactActionCatalog $catalog,
        private readonly ImpactService $impactService,
    ) {
    }

    public function create(User $user, array $data): Impact
    {
        return DB::transaction(function () use ($user, $data): Impact {
            $action = $this->catalog->get((string) $data['action_key']);

            if (! $action) {
                throw new \InvalidArgumentException('Invalid life impact action key.');
            }

            $requiresApproval = (bool) config('impact.requires_leadership_approval', true);
            $status = $requiresApproval ? 'pending' : 'approved';
            $approvedAt = $status === 'approved' ? now() : null;

            $impact = Impact::query()->create([
                'user_id' => $user->id,
                'impacted_peer_id' => $data['impacted_peer_id'] ?? null,
                'impact_date' => $data['date'] ?? now()->toDateString(),
                'action' => $action['key'],
                'story_to_share' => trim((string) ($data['remarks'] ?? '')),
                'life_impacted' => $action['life_impacted'],
                'additional_remarks' => $action['label'],
                'requires_leadership_approval' => $requiresApproval,
                'status' => $status,
                'approved_at' => $approvedAt,
                'timeline_posted_at' => $approvedAt,
            ]);

            if ($status === 'approved') {
                $this->impactService->recalculateUserLifeImpactedCount((string) $user->id);
            }

            Log::info('life_impact.created', [
                'impact_id' => (string) $impact->id,
                'user_id' => (string) $user->id,
                'action_key' => $action['key'],
                'status' => $status,
                'life_impacted' => $action['life_impacted'],
                'requires_approval' => $requiresApproval,
            ]);

            return $impact;
        });
    }

    public function isLifeImpactAction(string $actionKey): bool
    {
        return $this->catalog->get($actionKey) !== null;
    }

    public function recalculateTotalForUser(User|string $userOrId): int
    {
        return $this->impactService->recalculateUserLifeImpactedCount($userOrId);
    }
}

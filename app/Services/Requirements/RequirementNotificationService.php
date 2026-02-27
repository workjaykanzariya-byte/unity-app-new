<?php

namespace App\Services\Requirements;

use App\Models\Requirement;
use App\Models\User;
use App\Services\Notifications\NotifyUserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class RequirementNotificationService
{
    public function __construct(private readonly NotifyUserService $notifyUserService)
    {
    }

    public function notifyRequirementCreated(Requirement $requirement): int
    {
        $creator = $requirement->user;

        if (! $creator) {
            return 0;
        }

        $notifiedCount = 0;

        User::query()
            ->where('id', '!=', $creator->id)
            ->whereNull('deleted_at')
            ->when(Schema::hasColumn('users', 'status'), fn ($query) => $query->where('status', 'active'))
            ->when(Schema::hasColumn('users', 'gdpr_deleted_at'), fn ($query) => $query->whereNull('gdpr_deleted_at'))
            ->when(Schema::hasColumn('users', 'anonymized_at'), fn ($query) => $query->whereNull('anonymized_at'))
            ->orderBy('id')
            ->chunkById(500, function ($users) use ($creator, $requirement, &$notifiedCount): void {
                $notificationPayload = [
                    'notification_type' => 'requirement_created',
                    'requirement_id' => (string) $requirement->id,
                    'from_user' => [
                        'id' => (string) $creator->id,
                        'name' => $this->resolveUserName($creator),
                        'company' => $this->resolveUserCompany($creator),
                        'city' => (string) ($creator->city ?? ''),
                    ],
                ];

                $now = now();
                $rows = [];

                foreach ($users as $user) {
                    $rows[] = [
                        'user_id' => (string) $user->id,
                        'type' => 'requirement_created',
                        'payload' => json_encode($notificationPayload),
                        'is_read' => false,
                        'created_at' => $now,
                        'read_at' => null,
                    ];
                }

                if ($rows === []) {
                    return;
                }

                try {
                    DB::table('notifications')->insert($rows);
                    $notifiedCount += count($rows);
                } catch (Throwable $exception) {
                    // Manual SQL when enum blocks inserts in some environments:
                    // ALTER TYPE notification_type_enum ADD VALUE IF NOT EXISTS 'requirement_created';
                    Log::error('Requirement notification bulk insert failed', [
                        'requirement_id' => (string) $requirement->id,
                        'error' => $exception->getMessage(),
                        'trace' => $exception->getTraceAsString(),
                    ]);
                }
            }, 'id');

        return $notifiedCount;
    }

    public function notifyRequirementInterest(Requirement $requirement, User $interestedUser, ?string $comment): void
    {
        $creator = $requirement->user;

        if (! $creator || (string) $creator->id === (string) $interestedUser->id) {
            return;
        }

        try {
            $this->notifyUserService->notifyUser(
                $creator,
                $interestedUser,
                'requirement_interest',
                [
                    'notification_type' => 'requirement_interest',
                    'requirement_id' => (string) $requirement->id,
                    'requirement_subject' => $requirement->subject,
                    'from_user_id' => (string) $interestedUser->id,
                    'from_user_name' => $this->resolveUserName($interestedUser),
                    'from_company' => $this->resolveUserCompany($interestedUser),
                    'from_city' => $interestedUser->city,
                    'from_profile_photo_url' => $interestedUser->profile_photo_url,
                    'comment' => $comment,
                    'to_user_id' => (string) $creator->id,
                    'title' => 'New requirement interest',
                    'body' => $this->resolveUserName($interestedUser) . ' expressed interest in: ' . $requirement->subject,
                ],
                $requirement
            );
        } catch (Throwable $exception) {
            Log::warning('Requirement interest notification failed.', [
                'requirement_id' => (string) $requirement->id,
                'to_user_id' => (string) $creator->id,
                'from_user_id' => (string) $interestedUser->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function resolveUserName(User $user): string
    {
        return (string) ($user->display_name
            ?? $user->name
            ?? $user->full_name
            ?? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))
            ?? 'Peer');
    }

    private function resolveUserCompany(User $user): string
    {
        return (string) ($user->company_name ?? $user->company ?? '');
    }
}

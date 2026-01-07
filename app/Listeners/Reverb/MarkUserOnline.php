<?php

namespace App\Listeners\Reverb;

use App\Services\Presence\UserPresenceService;

class MarkUserOnline
{
    public function __construct(private readonly UserPresenceService $presence)
    {
    }

    public function handle(object $event): void
    {
        $userId = $this->resolveUserId($event);

        if (! $userId) {
            return;
        }

        $this->presence->markOnline($userId);
    }

    private function resolveUserId(object $event): ?string
    {
        $candidates = [
            data_get($event, 'connection.user.id'),
            data_get($event, 'connection.userId'),
            data_get($event, 'connection.user_id'),
            data_get($event, 'user.id'),
            data_get($event, 'userId'),
            data_get($event, 'user_id'),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate) {
                return (string) $candidate;
            }
        }

        return null;
    }
}

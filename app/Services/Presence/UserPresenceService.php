<?php

namespace App\Services\Presence;

use App\Events\User\UserPresenceUpdated;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class UserPresenceService
{
    public function markOnline(string $userId): void
    {
        $timestamp = now()->toIso8601String();

        Cache::put($this->cacheKey($userId), [
            'status' => 'online',
            'last_seen' => $timestamp,
        ], now()->addMinutes(10));

        broadcast(new UserPresenceUpdated($userId, 'online', $timestamp));
    }

    public function markOffline(string $userId): void
    {
        $timestamp = now()->toIso8601String();

        Cache::put($this->cacheKey($userId), [
            'status' => 'offline',
            'last_seen' => $timestamp,
        ], now()->addMinutes(10));

        broadcast(new UserPresenceUpdated($userId, 'offline', $timestamp));
    }

    private function cacheKey(string $userId): string
    {
        return 'presence:user:' . Str::lower($userId);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Events\User\UserPresenceUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PresenceController extends BaseApiController
{
    private const PRESENCE_TTL_SECONDS = 60;

    public function ping(Request $request)
    {
        $user = $request->user();
        $timestamp = now();

        Cache::put($this->presenceKey($user->id), [
            'status' => 'online',
            'last_seen_at' => $timestamp->toISOString(),
        ], self::PRESENCE_TTL_SECONDS);

        event(new UserPresenceUpdated($user->id, 'online', $timestamp));

        return $this->success([
            'status' => 'online',
            'last_seen_at' => $timestamp,
        ], 'Presence updated');
    }

    public function offline(Request $request)
    {
        $user = $request->user();
        $timestamp = now();

        Cache::put($this->presenceKey($user->id), [
            'status' => 'offline',
            'last_seen_at' => $timestamp->toISOString(),
        ], self::PRESENCE_TTL_SECONDS);

        event(new UserPresenceUpdated($user->id, 'offline', $timestamp));

        return $this->success([
            'status' => 'offline',
            'last_seen_at' => $timestamp,
        ], 'Presence updated');
    }

    private function presenceKey(string $userId): string
    {
        return 'presence:user:' . $userId;
    }
}

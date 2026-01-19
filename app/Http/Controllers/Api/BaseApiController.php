<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class BaseApiController extends Controller
{
    protected function success($data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function error(string $message, int $status = 400, $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    protected function buildActivityPostMessage(string $activityType, ?User $otherUser): string
    {
        $normalizedType = Str::of($activityType)->lower()->replace(' ', '_')->toString();
        $peerName = $this->resolvePeerName($otherUser);

        return match ($normalizedType) {
            'testimonial' => $peerName
                ? 'Hey Peers, sharing a moment of gratitude to ' . $peerName . '.'
                : 'Hey Peers, sharing a moment of gratitude.',
            'business_deal' => 'Hey Peers, another business connection and handshake turned into real results.',
            'p2p_meeting' => $peerName
                ? 'Hey Peers, I have connected with ' . $peerName . ', exchanged ideas, and discussed collaboration.'
                : 'Hey Peers, I had a peer-to-peer meeting and discussed collaboration.',
            default => '',
        };
    }

    protected function resolvePeerName(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        if (! empty($user->display_name)) {
            return $user->display_name;
        }

        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

        return $fullName !== '' ? $fullName : 'Peer';
    }
}

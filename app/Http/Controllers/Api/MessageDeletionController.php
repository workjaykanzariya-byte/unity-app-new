<?php

namespace App\Http\Controllers\Api;

use App\Models\Message;

class MessageDeletionController extends BaseApiController
{
    public function deleteForMe(Message $message)
    {
        $user = auth()->user();
        $chat = $message->chat;

        if (! $chat || ! $chat->canAccessChat($user)) {
            return $this->error('Forbidden', 403);
        }

        if ((string) $chat->user1_id === (string) $user->id) {
            $message->deleted_for_user1_at = now();
        } else {
            $message->deleted_for_user2_at = now();
        }

        $message->save();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted for you',
        ]);
    }

    public function deleteForEveryone(Message $message)
    {
        $user = auth()->user();
        $chat = $message->chat;

        if (! $chat || ! $chat->canAccessChat($user)) {
            return $this->error('Forbidden', 403);
        }

        if ((string) $message->sender_id !== (string) $user->id) {
            return $this->error('Forbidden', 403);
        }

        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted for everyone',
        ]);
    }
}

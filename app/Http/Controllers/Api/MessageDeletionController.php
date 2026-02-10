<?php

namespace App\Http\Controllers\Api;

use App\Models\Message;
use Illuminate\Http\Request;

class MessageDeletionController extends BaseApiController
{
    public function deleteForMe(Request $request, Message $message)
    {
        $authUser = $request->user();
        $chat = $message->chat;

        if (! $chat || ((string) $chat->user1_id !== (string) $authUser->id && (string) $chat->user2_id !== (string) $authUser->id)) {
            return $this->error('Forbidden', 403);
        }

        if ((string) $chat->user1_id === (string) $authUser->id) {
            $message->deleted_for_user1_at = now();
        } else {
            $message->deleted_for_user2_at = now();
        }

        $message->save();

        return $this->success(null, 'Message deleted for you');
    }

    public function deleteForEveryone(Request $request, Message $message)
    {
        $authUser = $request->user();
        $chat = $message->chat;

        if (! $chat || ((string) $chat->user1_id !== (string) $authUser->id && (string) $chat->user2_id !== (string) $authUser->id)) {
            return $this->error('Forbidden', 403);
        }

        if ((string) $message->sender_id !== (string) $authUser->id) {
            return $this->error('Only sender can delete for everyone', 403);
        }

        if ($message->deleted_at === null) {
            $message->delete();
        }

        return $this->success(null, 'Message deleted for everyone');
    }
}

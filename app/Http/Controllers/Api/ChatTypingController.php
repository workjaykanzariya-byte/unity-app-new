<?php

namespace App\Http\Controllers\Api;

use App\Events\Chat\UserTyping;
use App\Models\Chat;
use Illuminate\Http\Request;

class ChatTypingController extends BaseApiController
{
    public function store(Request $request, Chat $chat)
    {
        $authUser = $request->user();

        if (! in_array($authUser->id, [$chat->user1_id, $chat->user2_id], true)) {
            return $this->error('Chat not found', 404);
        }

        $data = $request->validate([
            'is_typing' => ['required', 'boolean'],
        ]);

        event(new UserTyping($chat->id, $authUser->id, $data['is_typing']));

        return $this->success([
            'chat_id' => $chat->id,
            'is_typing' => $data['is_typing'],
        ], 'Typing status updated');
    }
}

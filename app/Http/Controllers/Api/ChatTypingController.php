<?php

namespace App\Http\Controllers\Api;

use App\Events\Chat\ChatTyping;
use App\Models\Chat;
use App\Support\Chat\AuthorizesChatAccess;
use Illuminate\Http\Request;

class ChatTypingController extends BaseApiController
{
    use AuthorizesChatAccess;

    public function start(Request $request, Chat $chat)
    {
        return $this->updateTyping($request, $chat, true);
    }

    public function stop(Request $request, Chat $chat)
    {
        return $this->updateTyping($request, $chat, false);
    }

    private function updateTyping(Request $request, Chat $chat, bool $isTyping)
    {
        $user = $request->user();

        if (! $this->canAccessChat($user, $chat)) {
            return $this->error('Forbidden', 403);
        }

        broadcast(new ChatTyping($chat->id, $user->id, $isTyping))->toOthers();

        return $this->success([
            'chat_id' => (string) $chat->id,
            'user_id' => (string) $user->id,
            'is_typing' => $isTyping,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Chat\StoreChatRequest;
use App\Http\Requests\Chat\StoreMessageRequest;
use App\Http\Resources\ChatResource;
use App\Http\Resources\MessageResource;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

class ChatController extends BaseApiController
{
    public function listChats(Request $request)
    {
        $authUser = $request->user();

        $query = Chat::with(['user1', 'user2', 'lastMessage'])
            ->where(function ($q) use ($authUser) {
                $q->where('user1_id', $authUser->id)
                    ->orWhere('user2_id', $authUser->id);
            });

        $query->withCount(['messages as unread_count' => function ($q) use ($authUser) {
            $q->where('sender_id', '!=', $authUser->id)
                ->where('is_read', false)
                ->whereNull('deleted_at');
        }]);

        $chats = $query
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get();

        return $this->success(ChatResource::collection($chats));
    }

    public function storeChat(StoreChatRequest $request)
    {
        $authUserId = auth()->id();
        $data = $request->validated();
        $otherUserId = $data['user_id'] ?? $request->input('user_id');

        if ($authUserId === $otherUserId) {
            return $this->error('You cannot start a chat with yourself', 422);
        }

        $otherUser = User::find($otherUserId);
        if (! $otherUser) {
            return $this->error('User not found', 404);
        }

        $chat = Chat::where(function ($q) use ($authUserId, $otherUserId) {
                $q->where('user1_id', $authUserId)
                    ->where('user2_id', $otherUserId);
            })
            ->orWhere(function ($q) use ($authUserId, $otherUserId) {
                $q->where('user1_id', $otherUserId)
                    ->where('user2_id', $authUserId);
            })
            ->first();

        if (! $chat) {
            $chat = Chat::create([
                'user1_id' => $authUserId,
                'user2_id' => $otherUserId,
            ]);
        }

        $chat->load([
            'user1',
            'user2',
            'lastMessage',
        ]);

        $chat->loadCount(['messages as unread_count' => function ($q) use ($authUserId) {
            $q->where('sender_id', '!=', $authUserId)
                ->where('is_read', false)
                ->whereNull('deleted_at');
        }]);

        return $this->success(new ChatResource($chat), 'Chat ready');
    }

    public function showChat(Request $request, string $id)
    {
        $authUser = $request->user();

        $chat = Chat::with(['user1', 'user2', 'lastMessage'])
            ->where('id', $id)
            ->where(function ($q) use ($authUser) {
                $q->where('user1_id', $authUser->id)
                    ->orWhere('user2_id', $authUser->id);
            })
            ->first();

        if (! $chat) {
            return $this->error('Chat not found', 404);
        }

        $chat->loadCount(['messages as unread_count' => function ($q) use ($authUser) {
            $q->where('sender_id', '!=', $authUser->id)
                ->where('is_read', false)
                ->whereNull('deleted_at');
        }]);

        return $this->success(new ChatResource($chat));
    }

    public function listMessages(Request $request, string $id)
    {
        $authUser = $request->user();

        $chat = Chat::where('id', $id)
            ->where(function ($q) use ($authUser) {
                $q->where('user1_id', $authUser->id)
                    ->orWhere('user2_id', $authUser->id);
            })
            ->first();

        if (! $chat) {
            return $this->error('Chat not found', 404);
        }

        $perPage = (int) $request->input('per_page', 50);
        $perPage = max(1, min($perPage, 100));

        $paginator = Message::with('sender')
            ->where('chat_id', $chat->id)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);

        $data = [
            'items' => MessageResource::collection($paginator),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];

        return $this->success($data);
    }

    public function storeMessage(StoreMessageRequest $request, string $id)
    {
        $authUser = $request->user();
        $data = $request->validated();

        $chat = Chat::where('id', $id)
            ->where(function ($q) use ($authUser) {
                $q->where('user1_id', $authUser->id)
                    ->orWhere('user2_id', $authUser->id);
            })
            ->first();

        if (! $chat) {
            return $this->error('Chat not found', 404);
        }

        $message = new Message();
        $message->chat_id = $chat->id;
        $message->sender_id = $authUser->id;
        $message->content = $data['content'] ?? null;
        $message->attachments = $data['attachments'] ?? null;
        $message->is_read = false;
        $message->save();

        $chat->last_message_id = $message->id;
        $chat->last_message_at = $message->created_at;
        $chat->save();

        $message->load('sender');

        return $this->success(new MessageResource($message->fresh()), 'Message sent', 201);
    }

    public function markRead(Request $request, string $id)
    {
        $authUser = $request->user();

        $chat = Chat::where('id', $id)
            ->where(function ($q) use ($authUser) {
                $q->where('user1_id', $authUser->id)
                    ->orWhere('user2_id', $authUser->id);
            })
            ->first();

        if (! $chat) {
            return $this->error('Chat not found', 404);
        }

        $updated = Message::where('chat_id', $chat->id)
            ->where('sender_id', '!=', $authUser->id)
            ->where('is_read', false)
            ->whereNull('deleted_at')
            ->update([
                'is_read' => true,
                'updated_at' => now(),
            ]);

        return $this->success([
            'updated_count' => $updated,
        ], 'Messages marked as read');
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Chat\StoreChatRequest;
use App\Http\Requests\Chat\StoreMessageRequest;
use App\Http\Resources\ChatResource;
use App\Http\Resources\MessageResource;
use App\Models\Notification;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Events\Chat\ChatReadUpdated;
use App\Events\Chat\MessageSent;
use App\Events\Chat\TypingIndicator;
use App\Support\Chat\AuthorizesChatAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatController extends BaseApiController
{
    use AuthorizesChatAccess;

    public function index(Request $request)
    {
        return $this->listChats($request);
    }

    public function listChats(Request $request)
    {
        $authUser = $request->user();
        $me = $authUser->id;

        $query = Chat::with(['user1', 'user2', 'lastMessage'])
            ->where(function ($q) use ($me) {
                $q->where('user1_id', $me)
                    ->orWhere('user2_id', $me);
            });

        $query->withCount(['messages as unread_count' => function ($q) use ($authUser) {
            $q->where('sender_id', '!=', $authUser->id)
                ->where('is_read', false)
                ->whereNull('deleted_at');
        }]);

        $chats = $query
            ->orderByRaw('last_message_at IS NULL ASC')
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
        $data = $request->validated() ?? $request->all();

        $chat = Chat::where('id', $id)
            ->where(function ($q) use ($authUser) {
                $q->where('user1_id', $authUser->id)
                    ->orWhere('user2_id', $authUser->id);
            })
            ->first();

        if (! $chat) {
            return $this->error('Chat not found', 404);
        }

        $message = $chat->messages()->create([
            'sender_id' => $authUser->id,
            'content' => $data['content_text'] ?? $data['content'] ?? null,
            'attachments' => $data['attachments'] ?? null,
            'is_read' => false,
        ]);

        $message->refresh();
        $message->load('sender');

        $chat->last_message_id = $message->id;
        $chat->last_message_at = $message->created_at;
        $chat->save();

        $senderPayload = $this->formatUserPayload($authUser);
        $recipients = collect([$chat->user1_id, $chat->user2_id])
            ->filter()
            ->unique()
            ->reject(fn ($userId) => (string) $userId === (string) $authUser->id)
            ->values();

        foreach ($recipients as $recipientId) {
            Notification::create([
                'user_id' => $recipientId,
                'type' => 'new_message',
                'payload' => [
                    'chat_id' => (string) $chat->id,
                    'message_id' => (string) $message->id,
                    'sender' => $senderPayload,
                    'preview' => Str::limit((string) ($message->content ?? ''), 120, ''),
                    'type' => 'chat_message',
                ],
                'is_read' => false,
                'created_at' => now(),
                'read_at' => null,
            ]);
        }

        broadcast(new MessageSent($chat, $message, $authUser))->toOthers();

        return $this->success(new MessageResource($message), 'Message sent', 201);
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

        $readAt = now();

        $updated = Message::where('chat_id', $chat->id)
            ->where('sender_id', '!=', $authUser->id)
            ->where('is_read', false)
            ->whereNull('deleted_at')
            ->update([
                'is_read' => true,
                'updated_at' => $readAt,
            ]);

        broadcast(new ChatReadUpdated($chat, $authUser->id, $readAt))->toOthers();

        return $this->success([
            'updated_count' => $updated,
        ], 'Messages marked as read');
    }

    public function typing(Request $request, string $id)
    {
        $authUser = $request->user();
        $data = $request->validate([
            'is_typing' => ['required', 'boolean'],
        ]);

        $chat = Chat::where('id', $id)
            ->where(function ($q) use ($authUser) {
                $q->where('user1_id', $authUser->id)
                    ->orWhere('user2_id', $authUser->id);
            })
            ->first();

        if (! $chat) {
            return $this->error('Chat not found', 404);
        }

        broadcast(new TypingIndicator($chat, $authUser, (bool) $data['is_typing']))->toOthers();

        return $this->success([
            'is_typing' => (bool) $data['is_typing'],
        ], 'Typing updated');
    }

    private function formatUserPayload(User $user): array
    {
        return [
            'id' => (string) $user->id,
            'display_name' => $user->display_name
                ?? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
            'profile_photo_url' => $user->profile_photo_url,
        ];
    }

    // Testing notes:
    // - Start Reverb: php artisan reverb:start
    // - Send message as user A, listen as user B on private-chat.{chatId} => chat.message.sent
    // - Call typing endpoint => chat.typing
    // - Presence join/leave reflects online/offline in client
    // - Verify notifications row inserted for recipient user
}

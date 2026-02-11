<?php

namespace App\Http\Controllers\Api;

use App\Events\Chat\NewChatMessage;
use App\Http\Resources\MessageResource;
use App\Models\Chat;
use App\Models\FileModel;
use App\Support\Chat\AuthorizesChatAccess;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class MessageController extends BaseApiController
{
    use AuthorizesChatAccess;

    public function index(Request $request, Chat $chat)
    {
        $user = $request->user();

        if (! $this->canAccessChat($user, $chat)) {
            return $this->error('Forbidden', 403);
        }

        $perPage = max(1, min((int) $request->integer('per_page', 50), 100));

        $messages = $chat->messages()
            ->with('sender:id,display_name,first_name,last_name,profile_photo_url')
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return $this->success($messages->through(fn ($message) => new MessageResource($message)));
    }

    public function store(Request $request, Chat $chat)
    {
        $user = $request->user();

        if (! $this->canAccessChat($user, $chat)) {
            return $this->error('Forbidden', 403);
        }

        $validated = $request->validate([
            'content' => ['nullable', 'string'],
            'files' => ['nullable'],
            'files.*' => ['file', 'max:20480'],
            'files[]' => ['nullable'],
            'files[].*' => ['file', 'max:20480'],
        ]);

        $uploadedFiles = $request->file('files');
        if ($uploadedFiles === null) {
            $uploadedFiles = $request->file('files[]');
        }
        if ($uploadedFiles === null) {
            $uploadedFiles = [];
        }
        if (! is_array($uploadedFiles)) {
            $uploadedFiles = [$uploadedFiles];
        }

        $hasContent = ! empty(trim((string) $request->input('content')));
        $hasAnyFile = count($uploadedFiles) > 0;

        if (! $hasContent && ! $hasAnyFile) {
            return response()->json([
                'message' => 'Either content or attachments is required.',
                'errors' => [
                    'content' => ['Either content or attachments is required.'],
                ],
            ], 422);
        }

        $content = $validated['content'] ?? null;

        $attachments = [];
        foreach ($uploadedFiles as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $attachments[] = $this->storeAttachment($file, $user->id);
        }

        $message = $chat->messages()->create([
            'sender_id' => $user->id,
            'content' => $content,
            'attachments' => $attachments ?: null,
            'is_read' => false,
        ]);

        $chat->forceFill([
            'last_message_at' => now(),
            'last_message_id' => $message->id,
        ])->save();

        $message->load('sender:id,display_name,first_name,last_name,profile_photo_url');

        broadcast(new NewChatMessage($chat, $message))->toOthers();

        return $this->success(new MessageResource($message), 'Message sent', 201);
    }

    private function storeAttachment(UploadedFile $file, string $uploaderUserId): array
    {
        $disk = config('filesystems.default', 'public');
        $folder = 'uploads/' . now()->format('Y/m/d');
        $safeName = preg_replace('/[^A-Za-z0-9\.\-_]/', '_', $file->getClientOriginalName());
        $name = (string) Str::uuid() . '_' . ($safeName ?: 'attachment');
        $path = $file->storeAs($folder, $name, $disk);

        $fileModel = FileModel::create([
            'uploader_user_id' => $uploaderUserId,
            's3_key' => $path,
            'mime_type' => $file->getMimeType() ?: $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
        ]);

        $mime = (string) $fileModel->mime_type;
        $kind = Str::startsWith($mime, 'image/')
            ? 'image'
            : (Str::startsWith($mime, 'video/') ? 'video' : 'file');

        return [
            'file_id' => (string) $fileModel->id,
            'kind' => $kind,
            'name' => $file->getClientOriginalName(),
            'mime' => $mime,
            'size' => (int) $fileModel->size_bytes,
            'url' => '/api/v1/files/' . $fileModel->id,
        ];
    }
}

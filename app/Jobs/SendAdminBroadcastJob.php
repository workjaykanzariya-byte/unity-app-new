<?php

namespace App\Jobs;

use App\Models\AdminBroadcast;
use App\Models\AdminUser;
use App\Models\Notification;
use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendAdminBroadcastJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $broadcastId,
        public ?string $chunkStartUserId = null,
        public int $chunkSize = 500,
    ) {
    }

    public function handle(): void
    {
        $broadcast = AdminBroadcast::query()->find($this->broadcastId);

        if (! $broadcast || $broadcast->status !== 'sending') {
            return;
        }

        try {
            $users = User::query()
                ->where('status', 'active')
                ->when($this->chunkStartUserId, fn ($query) => $query->where('id', '>', $this->chunkStartUserId))
                ->orderBy('id')
                ->limit($this->chunkSize)
                ->get(['id', 'status']);

            if ($users->isEmpty()) {
                $this->finalizeBroadcast($broadcast);

                return;
            }

            $now = now();
            $title = $broadcast->title ?: 'Peers Global Unity';
            $senderUserId = $this->resolveSenderUserId($broadcast);

            $rows = [];
            foreach ($users as $user) {
                $rows[] = [
                    'user_id' => $user->id,
                    'type' => 'admin_broadcast',
                    'payload' => json_encode([
                        'notification_type' => 'admin_broadcast',
                        'title' => $title,
                        'body' => $broadcast->message,
                        'from_user_id' => $senderUserId,
                        'to_user_id' => (string) $user->id,
                        'data' => [
                            'broadcast_id' => (string) $broadcast->id,
                            'image_file_id' => $broadcast->image_file_id,
                        ],
                        'notifiable_type' => AdminBroadcast::class,
                        'notifiable_id' => (string) $broadcast->id,
                    ], JSON_THROW_ON_ERROR),
                    'is_read' => false,
                    'created_at' => $now,
                    'read_at' => null,
                ];
            }

            Notification::query()->insert($rows);

            $failureCount = 0;

            foreach ($users as $user) {
                try {
                    SendPushNotificationJob::dispatch(
                        $user,
                        $title,
                        $broadcast->message,
                        [
                            'type' => 'admin_broadcast',
                            'broadcast_id' => (string) $broadcast->id,
                            'image_file_id' => $broadcast->image_file_id,
                            'image_url' => $broadcast->image_file_id ? url('/api/v1/files/' . $broadcast->image_file_id) : null,
                        ]
                    );
                } catch (Throwable $e) {
                    $failureCount++;
                    Log::error('Broadcast push dispatch failed.', [
                        'broadcast_id' => (string) $broadcast->id,
                        'user_id' => (string) $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $broadcast->increment('sent_count', $users->count());
            $broadcast->increment('success_count', $users->count() - $failureCount);
            $broadcast->increment('failure_count', $failureCount);

            if ($users->count() < $this->chunkSize) {
                $this->finalizeBroadcast($broadcast->fresh());

                return;
            }

            self::dispatch($this->broadcastId, (string) $users->last()->id, $this->chunkSize);
        } catch (Throwable $e) {
            Log::error('Broadcast send job failed.', [
                'broadcast_id' => (string) $this->broadcastId,
                'chunk_start_user_id' => $this->chunkStartUserId,
                'error' => $e->getMessage(),
            ]);

            $fresh = AdminBroadcast::query()->find($this->broadcastId);
            if ($fresh) {
                $fresh->status = $fresh->isRecurring() ? 'scheduled' : 'draft';
                $fresh->next_run_at = $fresh->isRecurring() ? $fresh->computeNextRunAt() : null;
                $fresh->save();
            }

            throw $e;
        }
    }

    private function finalizeBroadcast(AdminBroadcast $broadcast): void
    {
        if ($broadcast->isRecurring()) {
            $broadcast->status = 'scheduled';
            $broadcast->last_sent_at = now();
            $broadcast->next_run_at = $broadcast->computeNextRunAt(now()->addSecond());
            $broadcast->save();

            return;
        }

        $broadcast->status = 'sent';
        $broadcast->last_sent_at = now();
        $broadcast->next_run_at = null;
        $broadcast->save();
    }

    private function resolveSenderUserId(AdminBroadcast $broadcast): ?string
    {
        if (! $broadcast->created_by_admin_id) {
            return null;
        }

        $admin = AdminUser::query()->find($broadcast->created_by_admin_id);
        $sender = AdminAccess::resolveAppUser($admin);

        return $sender?->id ? (string) $sender->id : null;
    }
}

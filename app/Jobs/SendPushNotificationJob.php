<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Firebase\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public string $title,
        public string $body,
        public array $data = []
    ) {
    }

    public function handle(FcmService $fcmService): void
    {
        try {
            $imageUrl = $this->data['image_url'] ?? null;
            $hasImage = is_string($imageUrl) && $imageUrl !== '';

            Log::info('SendPushNotificationJob started', [
                'user_id' => $this->user->id,
                'has_image' => $hasImage,
            ]);

            if (($this->user->status ?? null) !== 'active') {
                return;
            }

            $tokens = $this->user->pushTokens()->get();

            foreach ($tokens as $token) {
                try {
                    Log::info('Sending push to token', [
                        'token_prefix' => substr((string) $token->token, 0, 20) . '...',
                        'has_image' => $hasImage,
                        'image_url_prefix' => $hasImage ? substr((string) $imageUrl, 0, 40) . '...' : null,
                    ]);

                    $fcmService->sendToToken(
                        (string) $token->token,
                        $this->title,
                        $this->body,
                        $this->data,
                    );

                    Log::info('Push sent successfully');
                } catch (Throwable $e) {
                    Log::error('Push send failed', [
                        'error' => $e->getMessage(),
                    ]);

                    report($e);
                }
            }
        } catch (Throwable $throwable) {
            report($throwable);
        }
    }
}

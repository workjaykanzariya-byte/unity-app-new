<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Firebase\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
            if (($this->user->status ?? null) !== 'active') {
                return;
            }

            $tokens = $this->user->pushTokens()->get();

            foreach ($tokens as $pushToken) {
                try {
                    $fcmService->sendToToken(
                        (string) $pushToken->token,
                        $this->title,
                        $this->body,
                        $this->data,
                    );
                } catch (Throwable $throwable) {
                    report($throwable);
                }
            }
        } catch (Throwable $throwable) {
            report($throwable);
        }
    }
}

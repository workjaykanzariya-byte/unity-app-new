<?php

namespace App\Services\CoinClaims;

use App\Mail\CoinClaimApprovedMail;
use App\Mail\CoinClaimRejectedMail;
use App\Mail\CoinClaimSubmittedMail;
use App\Models\CoinClaimRequest;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CoinClaimEmailService
{
    public function sendSubmittedEmails(CoinClaimRequest $claim, ?User $user): void
    {
        if (! config('coin_claims.email_enabled', env('COIN_CLAIM_EMAIL_ENABLED', true))) {
            return;
        }

        if ($user && ! empty($user->email)) {
            $this->deliver($user->email, new CoinClaimSubmittedMail($claim), [
                'stage' => 'submitted_user',
                'coin_claim_request_id' => (string) $claim->id,
                'user_id' => (string) $user->id,
            ]);
        }

        $adminEmail = env('COIN_CLAIM_ADMIN_EMAIL');
        if (! empty($adminEmail)) {
            $this->deliver((string) $adminEmail, new CoinClaimSubmittedMail($claim), [
                'stage' => 'submitted_admin',
                'coin_claim_request_id' => (string) $claim->id,
            ]);
        }
    }

    public function sendApprovedEmail(CoinClaimRequest $claim, ?User $user, ?int $newBalance = null): void
    {
        if (! config('coin_claims.email_enabled', env('COIN_CLAIM_EMAIL_ENABLED', true)) || ! $user || empty($user->email)) {
            return;
        }

        $this->deliver($user->email, new CoinClaimApprovedMail($claim, $newBalance), [
            'stage' => 'approved_user',
            'coin_claim_request_id' => (string) $claim->id,
            'user_id' => (string) $user->id,
        ]);
    }

    public function sendRejectedEmail(CoinClaimRequest $claim, ?User $user): void
    {
        if (! config('coin_claims.email_enabled', env('COIN_CLAIM_EMAIL_ENABLED', true)) || ! $user || empty($user->email)) {
            return;
        }

        $this->deliver($user->email, new CoinClaimRejectedMail($claim), [
            'stage' => 'rejected_user',
            'coin_claim_request_id' => (string) $claim->id,
            'user_id' => (string) $user->id,
        ]);
    }

    private function deliver(string $to, Mailable $mailable, array $context = []): void
    {
        try {
            Mail::to($to)->send($mailable);
            Log::info('Coin claim email sent', array_merge($context, ['to' => $to]));
        } catch (Throwable $e) {
            Log::error('Failed to send coin claim email', array_merge($context, [
                'to' => $to,
                'error' => $e->getMessage(),
            ]));
        }
    }
}

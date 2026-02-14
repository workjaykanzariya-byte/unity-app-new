<?php

namespace App\Mail;

use App\Models\CoinClaimRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CoinClaimApprovedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public CoinClaimRequest $claim, public ?int $newBalance = null)
    {
        $this->claim->loadMissing('user');
    }

    public function build(): self
    {
        $label = (string) config('coins.claim_coin_labels.'.$this->claim->activity_code, $this->claim->activity_code);

        return $this->subject('Peers Global Unity: Coin Claim Approved - '.$label)
            ->view('emails.coin_claim_approved', [
                'appName' => 'Peers Global Unity',
                'claim' => $this->claim,
                'activityLabel' => $label,
                'summary' => array_filter($this->claim->formattedPayloadForEmail(), fn ($value) => filled($value)),
                'newBalance' => $this->newBalance,
            ]);
    }
}

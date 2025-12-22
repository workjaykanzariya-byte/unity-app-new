<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ActivityOtherUserMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $activityTypeNormalized,
        public string $activityTypeLabel,
        public array $viewData,
    ) {
    }

    public function build(): self
    {
        $subject = sprintf('Peers Global Unity: %s created a %s with you', $this->viewData['actorName'] ?? 'Someone', $this->activityTypeLabel);

        return $this->subject($subject)
            ->view($this->resolveView())
            ->with($this->viewData);
    }

    protected function resolveView(): string
    {
        return match ($this->activityTypeNormalized) {
            'p2p_meeting' => 'emails.p2p_receiver',
            'referral' => 'emails.referral_receiver',
            'testimonial' => 'emails.testimonial_receiver',
            'business_deal' => 'emails.business_deal_receiver',
            default => 'emails.p2p_receiver',
        };
    }
}

<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ActivityActorMail extends Mailable
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
        $subject = sprintf('Peers Global Unity: Your %s has been recorded', $this->activityTypeLabel);

        return $this->subject($subject)
            ->view($this->resolveView())
            ->with($this->viewData);
    }

    protected function resolveView(): string
    {
        return match ($this->activityTypeNormalized) {
            'p2p_meeting' => 'emails.p2p_sender',
            'referral' => 'emails.referral_sender',
            'requirement' => 'emails.requirement_sender',
            'testimonial' => 'emails.testimonial_sender',
            'business_deal' => 'emails.business_deal_sender',
            default => 'emails.p2p_sender',
        };
    }
}

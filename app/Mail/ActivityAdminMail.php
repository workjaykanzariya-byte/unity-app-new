<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ActivityAdminMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $activityType,
        public string $activityTitle,
        public ?User $actor,
        public array $activityAttributes,
    ) {
    }

    public function build(): self
    {
        $subject = 'Peers Global Unity: New Requirement submitted';

        return $this->subject($subject)
            ->view('emails.activity_admin')
            ->with([
                'activityType' => $this->activityType,
                'activityTitle' => $this->activityTitle,
                'actor' => $this->actor,
                'activityAttributes' => $this->activityAttributes,
            ]);
    }
}

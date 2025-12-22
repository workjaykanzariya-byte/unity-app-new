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
        public string $activityType,
        public string $activityTitle,
        public ?User $actor,
        public ?User $otherUser,
        public array $activityAttributes,
    ) {
    }

    public function build(): self
    {
        $actorName = $this->actor?->display_name
            ?? trim(($this->actor?->first_name ?? '') . ' ' . ($this->actor?->last_name ?? ''))
            ?: 'Someone';

        $subject = sprintf('Peers Global Unity: %s created a %s with you', $actorName, $this->activityType);

        return $this->subject($subject)
            ->view('emails.activity_other_user')
            ->with([
                'activityType' => $this->activityType,
                'activityTitle' => $this->activityTitle,
                'actor' => $this->actor,
                'otherUser' => $this->otherUser,
                'activityAttributes' => $this->activityAttributes,
            ]);
    }
}

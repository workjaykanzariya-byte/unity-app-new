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
        public string $activityType,
        public string $activityTitle,
        public ?User $actor,
        public ?User $otherUser,
        public array $activityAttributes,
    ) {
    }

    public function build(): self
    {
        $subject = sprintf('Peers Global Unity: Your %s has been recorded', $this->activityType);

        return $this->subject($subject)
            ->view('emails.activity_actor')
            ->with([
                'activityType' => $this->activityType,
                'activityTitle' => $this->activityTitle,
                'actor' => $this->actor,
                'otherUser' => $this->otherUser,
                'activityAttributes' => $this->activityAttributes,
            ]);
    }
}

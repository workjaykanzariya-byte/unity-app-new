<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomePeerMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $payload;

    public function __construct(array $payload = [])
    {
        $this->payload = $payload;
    }

    public function build()
    {
        return $this->subject('Welcome to Peers Global')
            ->view('emails.welcome_peer')
            ->with([
                'name' => (string) ($this->payload['name'] ?? ''),
                'email' => (string) ($this->payload['email'] ?? ''),
            ]);
    }
}

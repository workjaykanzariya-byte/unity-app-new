<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminLoginOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $otp)
    {
    }

    public function build(): self
    {
        return $this->subject('Your Admin Login OTP')
            ->view('emails.admin_login_otp', [
                'otp' => $this->otp,
            ]);
    }
}

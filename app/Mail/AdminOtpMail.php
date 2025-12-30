<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;
    public int $expiryMinutes;
    public string $appName;

    public function __construct(string $otp, int $expiryMinutes = 10, ?string $appName = null)
    {
        $this->otp = $otp;
        $this->expiryMinutes = $expiryMinutes;
        $this->appName = $appName ?? config('app.name', 'Admin Panel');
    }

    public function build(): self
    {
        return $this->subject('Your admin panel login code')
            ->view('emails.admin-otp')
            ->with([
                'otp' => $this->otp,
                'expiryMinutes' => $this->expiryMinutes,
                'appName' => $this->appName,
            ]);
    }
}

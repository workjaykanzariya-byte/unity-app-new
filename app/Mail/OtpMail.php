<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $otp;
    public string $subjectLine;
    public string $line1;

    public function __construct(string $otp, string $subjectLine, string $line1)
    {
        $this->otp = $otp;
        $this->subjectLine = $subjectLine;
        $this->line1 = $line1;
    }

    public function build(): self
    {
        return $this->subject($this->subjectLine)
            ->view('emails.otp')
            ->with([
                'otp' => $this->otp,
                'line1' => $this->line1,
            ]);
    }
}

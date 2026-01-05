<?php

namespace App\Mail;

use App\Models\AdminUser;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminLoginOtpMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $otp;

    public AdminUser $adminUser;

    public function __construct(string $otp, AdminUser $adminUser)
    {
        $this->otp = $otp;
        $this->adminUser = $adminUser;
    }

    public function build()
    {
        return $this->subject('Your Admin Panel Login OTP')
            ->view('admin.emails.login-otp')
            ->with([
                'otp' => $this->otp,
                'adminUser' => $this->adminUser,
            ]);
    }
}

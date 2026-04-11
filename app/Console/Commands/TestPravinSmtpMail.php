<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TestPravinSmtpMail extends Command
{
    protected $signature = 'mail:test-pravin-smtp {email : Recipient email address for the test message}';

    protected $description = 'Send a diagnostic test email using smtp_pravin mailer settings.';

    public function handle(): int
    {
        $recipient = trim((string) $this->argument('email'));

        if ($recipient === '') {
            $this->error('Recipient email is required.');

            return self::INVALID;
        }

        $mailer = 'smtp_pravin';
        $fromAddress = trim((string) config('mail.from_pravin.address', 'pravin@peersglobal.com'));
        $fromName = trim((string) config('mail.from_pravin.name', 'Peers Global Unity'));

        $smtpHost = trim((string) config('mail.mailers.smtp_pravin.host', ''));
        $smtpPort = (string) config('mail.mailers.smtp_pravin.port', '');
        $smtpUsername = trim((string) config('mail.mailers.smtp_pravin.username', ''));
        $smtpEncryption = trim((string) config('mail.mailers.smtp_pravin.encryption', ''));
        $passwordExists = trim((string) config('mail.mailers.smtp_pravin.password', '')) !== '';

        Log::info('mail.test_pravin_smtp.started', [
            'mailer' => $mailer,
            'to' => $recipient,
            'from_address' => $fromAddress,
            'from_name' => $fromName,
            'smtp_host' => $smtpHost,
            'smtp_port' => $smtpPort,
            'smtp_username' => $smtpUsername,
            'smtp_encryption' => $smtpEncryption,
            'smtp_password_exists' => $passwordExists,
            'delivery_mode' => 'immediate',
        ]);

        try {
            Mail::mailer($mailer)
                ->to($recipient)
                ->send(new class($fromAddress, $fromName) extends \Illuminate\Mail\Mailable
                {
                    public function __construct(private readonly string $fromAddress, private readonly string $fromName)
                    {
                    }

                    public function build(): self
                    {
                        return $this->from($this->fromAddress, $this->fromName)
                            ->subject('Pravin SMTP Diagnostic Test')
                            ->html('This is a diagnostic email sent using smtp_pravin.');
                    }
                });

            Log::info('mail.test_pravin_smtp.success', [
                'mailer' => $mailer,
                'to' => $recipient,
            ]);

            $this->info('Pravin SMTP test email sent successfully.');

            return self::SUCCESS;
        } catch (Throwable $throwable) {
            Log::warning('mail.test_pravin_smtp.failed', [
                'mailer' => $mailer,
                'to' => $recipient,
                'message' => $throwable->getMessage(),
            ]);

            $this->error('Pravin SMTP test failed: '.$throwable->getMessage());

            return self::FAILURE;
        }
    }
}

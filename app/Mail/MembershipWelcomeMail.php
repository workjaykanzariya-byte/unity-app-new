<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MembershipWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    /**
     * @var array<int, array{path:string,name:string}>
     */
    public array $attachmentsConfig;

    /**
     * @var array<int, array{url:string,name:string}>
     */
    private const REQUIRED_ATTACHMENTS = [
        [
            'url' => 'https://peersunity.com/storage/uploads/2026/04/11/ab990575-908d-448e-bffc-e2ce48fc3306.pdf',
            'name' => 'Training Guide for Peers - Peers Global.pdf',
        ],
        [
            'url' => 'https://peersunity.com/storage/uploads/2026/04/11/0b3434f0-6fc2-4b2d-8358-cf909e9939e2.pdf',
            'name' => 'Circle Members Playbook - Peers Global.pdf',
        ],
    ];

    /**
     * @param  array<int, array{path:string,name:string}>  $attachmentsConfig
     */
    public function __construct(User $user, array $attachmentsConfig = [])
    {
        $this->user = $user;
        $this->attachmentsConfig = $attachmentsConfig;
    }

    public function build()
    {
        Log::info('membership.welcome_email.build_started', [
            'user_id' => (string) $this->user->id,
        ]);

        $selectedMailer = 'smtp_pravin';
        $fromAddress = trim((string) config('mail.from_pravin.address', 'pravin@peersglobal.com'));
        $fromName = trim((string) config('mail.from_pravin.name', 'Peers Global Unity'));
        $pravinHost = trim((string) config('mail.mailers.smtp_pravin.host', ''));
        $pravinPort = (string) config('mail.mailers.smtp_pravin.port', '');
        $pravinEncryption = trim((string) config('mail.mailers.smtp_pravin.encryption', ''));
        $pravinUsername = trim((string) config('mail.mailers.smtp_pravin.username', ''));
        $pravinPassword = trim((string) config('mail.mailers.smtp_pravin.password', ''));

        Log::info('membership.welcome_email.mailer_selection', [
            'user_id' => (string) $this->user->id,
            'mailer' => $selectedMailer,
            'smtp_host' => $pravinHost,
            'smtp_port' => $pravinPort,
            'smtp_username' => $pravinUsername,
            'smtp_encryption' => $pravinEncryption,
            'smtp_password_exists' => $pravinPassword !== '',
            'from_address' => $fromAddress,
            'from_name' => $fromName,
            'smtp_pravin_config_detected' => $pravinHost !== '' && $pravinUsername !== '' && $pravinPassword !== '',
            'delivery_mode' => 'immediate',
        ]);

        $mail = $this->mailer($selectedMailer)
            ->from($fromAddress, $fromName)
            ->subject('Welcome to your Peers Unity Membership')
            ->view('emails.membership.membership_welcome')
            ->with([
                'user' => $this->user,
            ]);

        if ($pravinHost === '' || $pravinUsername === '' || $pravinPassword === '') {
            Log::warning('membership.welcome_email.pravin_mailer_not_configured', [
                'user_id' => (string) $this->user->id,
                'configured_host' => $pravinHost !== '',
                'configured_port' => $pravinPort !== '',
                'configured_encryption' => $pravinEncryption !== '',
                'configured_username' => $pravinUsername !== '',
                'configured_password' => $pravinPassword !== '',
            ]);
        }

        foreach ($this->resolveAttachmentsForMail() as $attachment) {
            $mail->attach($attachment['path'], [
                'as' => $attachment['name'],
            ]);

            Log::info('membership.welcome_email.attachment_resolved', [
                'user_id' => (string) $this->user->id,
                'path' => $attachment['path'],
                'name' => $attachment['name'],
            ]);
        }

        return $mail;
    }

    /**
     * @return array<int, array{path:string,name:string}>
     */
    private function resolveAttachmentsForMail(): array
    {
        $attachments = [];

        foreach ($this->attachmentsConfig as $attachment) {
            $path = trim((string) ($attachment['path'] ?? ''));
            $name = trim((string) ($attachment['name'] ?? ''));

            if ($path === '' || ! is_file($path)) {
                Log::warning('membership.welcome_email.attachment_missing', [
                    'user_id' => (string) $this->user->id,
                    'path' => $path,
                ]);

                continue;
            }

            $attachments[$path] = [
                'path' => $path,
                'name' => $name !== '' ? $name : basename($path),
            ];
        }

        foreach (self::REQUIRED_ATTACHMENTS as $requiredAttachment) {
            $resolvedPath = $this->resolveLocalPathFromPublicStorageUrl($requiredAttachment['url']);

            if ($resolvedPath === null || ! is_file($resolvedPath)) {
                Log::warning('membership.welcome_email.required_attachment_missing', [
                    'user_id' => (string) $this->user->id,
                    'url' => $requiredAttachment['url'],
                    'resolved_path' => $resolvedPath,
                ]);

                continue;
            }

            $attachments[$resolvedPath] = [
                'path' => $resolvedPath,
                'name' => $requiredAttachment['name'],
            ];
        }

        return array_values($attachments);
    }

    private function resolveLocalPathFromPublicStorageUrl(string $url): ?string
    {
        $urlPath = parse_url($url, PHP_URL_PATH);
        if (! is_string($urlPath) || $urlPath === '' || ! str_starts_with($urlPath, '/storage/')) {
            return null;
        }

        $relativePath = ltrim(substr($urlPath, strlen('/storage/')), '/');

        $candidatePaths = [
            public_path('storage/' . $relativePath),
            storage_path('app/public/' . $relativePath),
        ];

        foreach ($candidatePaths as $candidatePath) {
            if (is_file($candidatePath)) {
                return $candidatePath;
            }
        }

        return $candidatePaths[0];
    }
}

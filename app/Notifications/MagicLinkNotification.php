<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\MailTemplateKey;
use App\Traits\BuildsMailTemplate;
use FinityLabs\FinMail\Mail\TemplateMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

final class MagicLinkNotification extends Notification implements ShouldQueue
{
    use BuildsMailTemplate;
    use Queueable;

    public function __construct(
        private readonly string $token,
        private readonly int $expiresInMinutes = 15
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * The login link is signed: the rendered body is kept out of the sent-emails log so that reading
     * the log does not let anyone sign in as the recipient.
     */
    public function toMail(object $notifiable): TemplateMail
    {
        $url = URL::temporarySignedRoute(
            'magicLink.verify',
            now()->addMinutes($this->expiresInMinutes),
            [
                'email' => $notifiable->email,
                'token' => $this->token,
            ]
        );

        return $this->mailTemplate(MailTemplateKey::MagicLink, $notifiable, [
            'login_url' => $url,
            'expires_in_minutes' => $this->expiresInMinutes,
        ])->withoutStoringRenderedBody();
    }
}

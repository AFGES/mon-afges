<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

final class MagicLinkNotification extends Notification implements ShouldQueue
{
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

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'magicLink.verify',
            now()->addMinutes($this->expiresInMinutes),
            [
                'email' => $notifiable->email,
                'token' => $this->token,
            ]
        );

        return (new MailMessage)
            ->subject('Votre lien de connexion')
            ->greeting('Bonjour !')
            ->line('Cliquez sur le bouton ci-dessous pour vous connecter à votre compte.')
            ->action('Se connecter', $url)
            ->line("Ce lien expirera dans {$this->expiresInMinutes} minutes.")
            ->line("Si vous n'avez pas demandé ce lien, ignorez simplement cet e-mail.");
    }
}

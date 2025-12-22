<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

final class ApprovalApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The translation key prefix for the notification (e.g., 'delegate', 'volunteer').
     */
    private string $translationKey;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Model $approvable,
        ?string $translationKey = null,
    ) {
        $this->translationKey = $translationKey ?? $this->guessTranslationKey();
    }

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
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__("{$this->translationKey}.notifications.approval_approved.subject"))
            ->greeting(__("{$this->translationKey}.notifications.approval_approved.greeting", ['name' => $notifiable->name]))
            ->line(__("{$this->translationKey}.notifications.approval_approved.intro"))
            ->line(__("{$this->translationKey}.notifications.approval_approved.body"))
            ->line(__("{$this->translationKey}.notifications.approval_approved.outro"));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'approvable_type' => $this->approvable::class,
            'approvable_id' => $this->approvable->getKey(),
        ];
    }

    /**
     * Guess the translation key from the model class name.
     */
    private function guessTranslationKey(): string
    {
        return Str::snake(class_basename($this->approvable));
    }
}

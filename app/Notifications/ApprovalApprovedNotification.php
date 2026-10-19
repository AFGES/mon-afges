<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\MailTemplateKey;
use App\Traits\BuildsMailTemplate;
use FinityLabs\FinMail\Mail\TemplateMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

final class ApprovalApprovedNotification extends Notification implements ShouldQueue
{
    use BuildsMailTemplate;
    use Queueable;

    /**
     * The approvable type (e.g., 'delegate', 'volunteer'), prefix of the mail template key.
     */
    private string $translationKey;

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

    public function toMail(object $notifiable): TemplateMail
    {
        return $this->mailTemplate(
            MailTemplateKey::from("{$this->translationKey}-approval-approved"),
            $notifiable,
            ['recipient_name' => $notifiable->name],
        );
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

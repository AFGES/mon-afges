<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Delegate;
use App\Models\Volunteer;
use App\Traits\EscapesMarkdown;
use Filament\Facades\Filament;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Asks approvers to review a pending application. The translation namespace (e.g. 'delegate'), the
 * '{namespace}s.approve|reject' routes and the Filament edit URL are all derived from the model class.
 */
final class ApprovalRequestedNotification extends Notification implements ShouldQueue
{
    use EscapesMarkdown;
    use Queueable;

    /**
     * The translation key prefix for the notification (e.g., 'delegate', 'volunteer').
     */
    private string $translationKey;

    public function __construct(
        public Delegate|Volunteer $approvable,
        ?string $translationKey = null,
    ) {
        $this->translationKey = $translationKey ?? Str::snake(class_basename($approvable));
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $key = "{$this->translationKey}.notifications.approval_requested";
        $routePrefix = Str::plural($this->translationKey);
        $routeParameters = [$this->translationKey => $this->approvable->getKey()];
        $expiry = now()->addDays(config('approval.link_expiry_days'));

        $approveUrl = URL::temporarySignedRoute("{$routePrefix}.approve", $expiry, $routeParameters);
        $rejectUrl = URL::temporarySignedRoute("{$routePrefix}.reject", $expiry, $routeParameters);
        $rejectLabel = __("{$key}.reject_action");

        return (new MailMessage)
            ->subject(__("{$key}.subject"))
            ->greeting(__("{$key}.greeting", ['name' => $this->escapeMarkdown($notifiable->name)]))
            ->line(__("{$key}.intro"))
            ->line(__("{$key}.applicant", [
                'name' => $this->escapeMarkdown($this->approvable->user->name),
            ]))
            ->line(__("{$key}.organization", [
                'organization' => $this->escapeMarkdown($this->approvable->organization->short_name),
            ]))
            ->line($this->detailLine($key))
            ->line(__("{$key}.started_at", [
                'date' => $this->approvable->started_at->format('d/m/Y'),
            ]))
            ->action(__("{$key}.approve_action"), $approveUrl)
            ->line("[{$rejectLabel}]({$rejectUrl})")
            ->line(__("{$key}.view_details", [
                'url' => Filament::getModelResource($this->approvable)::getUrl('edit', ['record' => $this->approvable]),
            ]))
            ->line(__("{$key}.outro"));
    }

    /**
     * The one line that differs per approvable: the seat of a delegate, the position of a volunteer.
     */
    private function detailLine(string $key): string
    {
        if ($this->approvable instanceof Delegate) {
            return (string) __("{$key}.seat", [
                'seat' => $this->escapeMarkdown($this->approvable->seat->name ?? 'N/A'),
            ]);
        }

        return (string) __("{$key}.position", [
            'position' => $this->escapeMarkdown((string) $this->approvable->position_title),
        ]);
    }
}

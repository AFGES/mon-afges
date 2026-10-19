<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\MailTemplateKey;
use App\Models\Delegate;
use App\Models\Volunteer;
use App\Traits\BuildsMailTemplate;
use Filament\Facades\Filament;
use FinityLabs\FinMail\Mail\TemplateMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Asks approvers to review a pending application. The approvable type (e.g. 'delegate'), the
 * '{type}-approval-requested' mail template, the '{type}s.approve|reject' routes and the Filament edit
 * URL are all derived from the model class.
 */
final class ApprovalRequestedNotification extends Notification implements ShouldQueue
{
    use BuildsMailTemplate;
    use Queueable;

    /**
     * The approvable type (e.g., 'delegate', 'volunteer').
     */
    private string $approvableType;

    public function __construct(
        public Delegate|Volunteer $approvable,
        ?string $approvableType = null,
    ) {
        $this->approvableType = $approvableType ?? Str::snake(class_basename($approvable));
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * The approve and reject links are signed: the rendered body is kept out of the sent-emails log so
     * that reading the log does not let anyone act on the application.
     */
    public function toMail(object $notifiable): TemplateMail
    {
        $routePrefix = Str::plural($this->approvableType);
        $routeParameters = [$this->approvableType => $this->approvable->getKey()];
        $expiry = now()->addDays(config('approval.link_expiry_days'));

        return $this->mailTemplate(
            MailTemplateKey::from("{$this->approvableType}-approval-requested"),
            $notifiable,
            [
                'recipient_name' => $notifiable->name,
                'applicant_name' => $this->approvable->user->name,
                'organization' => $this->approvable->organization->short_name,
                ...$this->detailToken(),
                'started_at' => $this->approvable->started_at->format('d/m/Y'),
                'approve_url' => URL::temporarySignedRoute("{$routePrefix}.approve", $expiry, $routeParameters),
                'reject_url' => URL::temporarySignedRoute("{$routePrefix}.reject", $expiry, $routeParameters),
                'details_url' => Filament::getModelResource($this->approvable)::getUrl('edit', ['record' => $this->approvable]),
            ],
        )->withoutStoringRenderedBody();
    }

    /**
     * The one token that differs per approvable: the seat of a delegate (its college and number, not the
     * label, which names the delegates already holding it), the position of a volunteer.
     *
     * @return array{seat: string}|array{position: string}
     */
    private function detailToken(): array
    {
        if ($this->approvable instanceof Delegate) {
            $seat = $this->approvable->seat;

            return ['seat' => $seat === null ? 'N/A' : "{$seat->collegeOrganization->college->name} #{$seat->seat_number}"];
        }

        return ['position' => (string) $this->approvable->position_title];
    }
}

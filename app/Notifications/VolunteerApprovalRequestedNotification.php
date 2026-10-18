<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Filament\Resources\Volunteers\VolunteerResource;
use App\Models\Volunteer;
use App\Traits\EscapesMarkdown;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

final class VolunteerApprovalRequestedNotification extends Notification implements ShouldQueue
{
    use EscapesMarkdown;
    use Queueable;

    public function __construct(
        public Volunteer $volunteer
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $approveUrl = URL::temporarySignedRoute(
            'volunteers.approve',
            now()->addDays(config('approval.link_expiry_days')),
            ['volunteer' => $this->volunteer->id]
        );

        $rejectUrl = URL::temporarySignedRoute(
            'volunteers.reject',
            now()->addDays(config('approval.link_expiry_days')),
            ['volunteer' => $this->volunteer->id]
        );

        $rejectLabel = __('volunteer.notifications.approval_requested.reject_action');

        return (new MailMessage)
            ->subject(__('volunteer.notifications.approval_requested.subject'))
            ->greeting(__('volunteer.notifications.approval_requested.greeting', ['name' => $this->escapeMarkdown($notifiable->name)]))
            ->line(__('volunteer.notifications.approval_requested.intro'))
            ->line(__('volunteer.notifications.approval_requested.applicant', [
                'name' => $this->escapeMarkdown($this->volunteer->user->name),
            ]))
            ->line(__('volunteer.notifications.approval_requested.organization', [
                'organization' => $this->escapeMarkdown($this->volunteer->organization->short_name),
            ]))
            ->line(__('volunteer.notifications.approval_requested.position', [
                'position' => $this->escapeMarkdown((string) $this->volunteer->position_title),
            ]))
            ->line(__('volunteer.notifications.approval_requested.started_at', [
                'date' => $this->volunteer->started_at->format('d/m/Y'),
            ]))
            ->action(
                __('volunteer.notifications.approval_requested.approve_action'),
                $approveUrl
            )
            ->line("[{$rejectLabel}]({$rejectUrl})")
            ->line(__('volunteer.notifications.approval_requested.view_details', [
                'url' => VolunteerResource::getUrl('edit', ['record' => $this->volunteer]),
            ]))
            ->line(__('volunteer.notifications.approval_requested.outro'));
    }
}

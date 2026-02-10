<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Delegate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class DelegateApprovalRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Delegate $delegate
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $approveUrl = \Illuminate\Support\Facades\URL::signedRoute(
            'delegates.approve',
            ['delegate' => $this->delegate->id]
        );

        $rejectUrl = \Illuminate\Support\Facades\URL::signedRoute(
            'delegates.reject',
            ['delegate' => $this->delegate->id]
        );

        return (new MailMessage)
            ->subject(__('delegate.notifications.approval_requested.subject'))
            ->greeting(__('delegate.notifications.approval_requested.greeting', ['name' => $notifiable->name]))
            ->line(__('delegate.notifications.approval_requested.intro'))
            ->line(__('delegate.notifications.approval_requested.applicant', [
                'name' => $this->delegate->user->name,
            ]))
            ->line(__('delegate.notifications.approval_requested.organization', [
                'organization' => $this->delegate->organization->short_name,
            ]))
            ->line(__('delegate.notifications.approval_requested.seat', [
                'seat' => $this->delegate->seat->name ?? 'N/A',
            ]))
            ->line(__('delegate.notifications.approval_requested.started_at', [
                'date' => $this->delegate->started_at->format('d/m/Y'),
            ]))
            ->action(
                __('delegate.notifications.approval_requested.approve_action'),
                $approveUrl
            )
            ->line('['.__('delegate.notifications.approval_requested.reject_action').']('.$rejectUrl.')')
            ->line(__('delegate.notifications.approval_requested.view_details', [
                'url' => route('filament.root.democratic-times.resources.delegates.edit', $this->delegate),
            ]))
            ->line(__('delegate.notifications.approval_requested.outro'));
    }
}

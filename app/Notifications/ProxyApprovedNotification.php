<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Proxy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ProxyApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Proxy $proxy) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('proxy.notifications.approved.subject'))
            ->line(__('proxy.notifications.approved.line1'))
            ->line(__('proxy.notifications.approved.line2', [
                'delegator' => $this->proxy->delegatorSeat->label(),
                'proxy_holder' => $this->proxy->proxyHolderSeat->label(),
                'session' => $this->proxy->democraticTimeSession->democraticTimeType->name,
            ]));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'proxy_id' => $this->proxy->id,
            'delegator_seat' => $this->proxy->delegatorSeat->label(),
            'proxy_holder_seat' => $this->proxy->proxyHolderSeat->label(),
            'session' => $this->proxy->democraticTimeSession->democraticTimeType->name,
        ];
    }
}

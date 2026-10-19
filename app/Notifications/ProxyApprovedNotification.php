<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\MailTemplateKey;
use App\Models\Proxy;
use App\Traits\BuildsMailTemplate;
use FinityLabs\FinMail\Mail\TemplateMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class ProxyApprovedNotification extends Notification implements ShouldQueue
{
    use BuildsMailTemplate;
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

    public function toMail(object $notifiable): TemplateMail
    {
        return $this->mailTemplate(MailTemplateKey::ProxyApproved, $notifiable, [
            'delegator' => $this->proxy->delegatorSeat->label(),
            'proxy_holder' => $this->proxy->proxyHolderSeat->label(),
            'session' => $this->proxy->democraticTimeSession->democraticTimeType->name,
        ]);
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

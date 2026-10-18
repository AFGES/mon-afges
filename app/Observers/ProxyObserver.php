<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ApprovalStatus;
use App\Models\Proxy;
use App\Notifications\ProxyApprovalNotification;
use App\Notifications\ProxyApprovedNotification;
use App\Observers\Concerns\NotifiesApprovers;
use Illuminate\Database\Eloquent\Builder;

final class ProxyObserver
{
    use NotifiesApprovers;

    public function created(Proxy $proxy): void
    {
        $this->notifyApprovers('Update:Proxy', new ProxyApprovalNotification($proxy));
    }

    public function updated(Proxy $proxy): void
    {
        if (! $proxy->wasChanged('approval_status')) {
            return;
        }

        if ($proxy->approval_status !== ApprovalStatus::Approved) {
            return;
        }

        $this->notifyDelegatesOfApproval($proxy);
    }

    /**
     * Send approval notification to the delegator seat's current delegate(s).
     */
    private function notifyDelegatesOfApproval(Proxy $proxy): void
    {
        $delegates = $proxy->delegatorSeat->delegates()
            ->where('started_at', '<=', now())
            ->where(function (Builder $query): void {
                $query->whereNull('ended_at')
                    ->orWhere('ended_at', '>=', now());
            })
            ->get();

        foreach ($delegates as $delegate) {
            $delegate->user?->notify(new ProxyApprovedNotification($proxy));
        }
    }
}

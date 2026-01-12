<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ApprovalStatus;
use App\Models\Proxy;
use App\Models\User;
use App\Notifications\ProxyApprovalNotification;
use Illuminate\Support\Facades\Notification;

final class ProxyObserver
{
    /**
     * Handle the Proxy "created" event.
     */
    public function created(Proxy $proxy): void
    {
        // Notify users who can approve proxies
        try {
            $approvers = User::permission('Update:Proxy')->get();

            if ($approvers->isNotEmpty()) {
                Notification::send($approvers, new ProxyApprovalNotification($proxy));
            }
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
            // Permission doesn't exist yet, skip notification
        }
    }

    /**
     * Handle the Proxy "updated" event.
     */
    public function updated(Proxy $proxy): void
    {
        // Send notification to the delegator seat's delegate when proxy is approved
        if ($proxy->wasChanged('approval_status') && $proxy->approval_status === ApprovalStatus::Approved) {
            $proxy->sendApprovalNotification();
        }
    }
}

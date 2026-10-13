<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ApprovalStatus;
use App\Models\Proxy;
use App\Models\User;
use App\Notifications\ProxyApprovalNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

final class ProxyObserver
{
    public function created(Proxy $proxy): void
    {
        // Notify users who can approve proxies
        try {
            $approvers = User::permission('Update:Proxy')->get();

            if ($approvers->isNotEmpty()) {
                Notification::send($approvers, new ProxyApprovalNotification($proxy));
            }
        } catch (PermissionDoesNotExist) {
            // Permission doesn't exist yet, skip notification
        }
    }

    public function updated(Proxy $proxy): void
    {
        if (! $proxy->wasChanged('approval_status')) {
            return;
        }

        if ($proxy->approval_status !== ApprovalStatus::Approved) {
            return;
        }

        $proxy->sendApprovalNotification();
    }
}

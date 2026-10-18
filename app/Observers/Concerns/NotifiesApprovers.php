<?php

declare(strict_types=1);

namespace App\Observers\Concerns;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

trait NotifiesApprovers
{
    /**
     * Notify every user holding the given permission. Skipped silently when the permission
     * does not exist yet or nobody holds it.
     */
    private function notifyApprovers(string $permission, Notification $notification): void
    {
        try {
            $approvers = User::permission($permission)->get();

            if ($approvers->isEmpty()) {
                return;
            }

            NotificationFacade::send($approvers, $notification);
        } catch (PermissionDoesNotExist) {
            // Permission doesn't exist yet, skip notification
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\User;
use App\Models\Volunteer;
use App\Notifications\VolunteerApprovalRequestedNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

final class VolunteerObserver
{
    public function created(Volunteer $volunteer): void
    {
        $this->notifyApprovers($volunteer);
    }

    /**
     * Notify users with approval permissions about the new volunteer application.
     */
    private function notifyApprovers(Volunteer $volunteer): void
    {
        try {
            $approvers = User::permission('Update:Volunteer')->get();

            if ($approvers->isEmpty()) {
                return;
            }

            Notification::send($approvers, new VolunteerApprovalRequestedNotification($volunteer));
        } catch (PermissionDoesNotExist) {
            // Permission doesn't exist yet, skip notification
        }
    }
}

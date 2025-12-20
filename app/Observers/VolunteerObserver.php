<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\User;
use App\Models\Volunteer;
use App\Notifications\VolunteerApprovalRequested;
use Illuminate\Support\Facades\Notification;

final class VolunteerObserver
{
    /**
     * Handle the Volunteer "created" event.
     */
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

            Notification::send($approvers, new VolunteerApprovalRequested($volunteer));
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
            // Permission doesn't exist yet, skip notification
            return;
        }
    }
}

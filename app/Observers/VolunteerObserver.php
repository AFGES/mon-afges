<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Volunteer;
use App\Notifications\ApprovalApprovedNotification;
use App\Notifications\ApprovalRequestedNotification;
use App\Observers\Concerns\NotifiesApprovers;

final class VolunteerObserver
{
    use NotifiesApprovers;

    public function created(Volunteer $volunteer): void
    {
        $this->notifyApprovers('Update:Volunteer', new ApprovalRequestedNotification($volunteer));
    }

    /**
     * Notify the volunteer's user once their application is approved.
     */
    public function updated(Volunteer $volunteer): void
    {
        if (! $volunteer->wasChanged('approval_status') || ! $volunteer->isApproved()) {
            return;
        }

        $volunteer->user?->notify(new ApprovalApprovedNotification($volunteer));
    }
}

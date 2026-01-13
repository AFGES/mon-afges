<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\DemocraticTimeType;

class DemocraticTimeTypeObserver
{
    /**
     * Handle the DemocraticTimeType "updated" event.
     * Update future sessions when quorum fields are changed.
     */
    public function updated(DemocraticTimeType $democraticTimeType): void
    {
        // Check if any quorum-related fields were changed
        $quorumFieldsChanged = $democraticTimeType->isDirty('calculate_quorum_per_college') ||
            $democraticTimeType->isDirty('quorum_percentage') ||
            $democraticTimeType->isDirty('include_proxies_in_quorum');

        if (! $quorumFieldsChanged) {
            return;
        }

        // Get all future sessions (started_at is in the future) for this type
        $futureSessions = $democraticTimeType->sessions()
            ->where('started_at', '>', now())
            ->get();

        // Update each future session with the new quorum values
        foreach ($futureSessions as $session) {
            $updates = [];

            if ($democraticTimeType->isDirty('calculate_quorum_per_college')) {
                $updates['calculate_quorum_per_college'] = $democraticTimeType->calculate_quorum_per_college;
            }

            if ($democraticTimeType->isDirty('quorum_percentage')) {
                $updates['quorum_percentage'] = $democraticTimeType->quorum_percentage;
            }

            if ($democraticTimeType->isDirty('include_proxies_in_quorum')) {
                $updates['include_proxies_in_quorum'] = $democraticTimeType->include_proxies_in_quorum;
            }

            if (! empty($updates)) {
                $session->update($updates);
            }
        }
    }
}

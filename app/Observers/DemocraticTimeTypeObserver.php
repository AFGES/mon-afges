<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\DemocraticTimeType;

final class DemocraticTimeTypeObserver
{
    /**
     * Handle the DemocraticTimeType "updated" event.
     * Update future sessions when quorum fields are changed.
     */
    public function updated(DemocraticTimeType $democraticTimeType): void
    {
        $quorumFieldsChanged = $democraticTimeType->isDirty('calculate_quorum_per_college') ||
            $democraticTimeType->isDirty('quorum_percentage') ||
            $democraticTimeType->isDirty('include_proxies_in_quorum');

        if (! $quorumFieldsChanged) {
            return;
        }

        $futureSessions = $democraticTimeType->sessions()
            ->where('started_at', '>', now())
            ->get();

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

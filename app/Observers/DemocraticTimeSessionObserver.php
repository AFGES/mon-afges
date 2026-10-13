<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\DemocraticTimeSession;
use Carbon\Carbon;

final class DemocraticTimeSessionObserver
{
    /**
     * Handle the DemocraticTimeSession "creating" event.
     * Copy quorum settings from the type to ensure they don't change if the type is modified later.
     */
    public function creating(DemocraticTimeSession $session): void
    {
        if (! $session->democraticTimeType) {
            return;
        }

        if ($session->calculate_quorum_per_college === null) {
            $session->calculate_quorum_per_college = $session->democraticTimeType->calculate_quorum_per_college;
        }

        if ($session->quorum_percentage === null) {
            $session->quorum_percentage = $session->democraticTimeType->quorum_percentage;
        }

        if ($session->include_proxies_in_quorum === null) {
            $session->include_proxies_in_quorum = $session->democraticTimeType->include_proxies_in_quorum;
        }
    }

    /**
     * Handle the DemocraticTimeSession "updated" event.
     * When ended_at changes, sync signed_out_at for sign-ins that match the old end date.
     */
    public function updated(DemocraticTimeSession $session): void
    {
        if (! $session->isDirty('ended_at')) {
            return;
        }

        $oldEndedAt = $session->getOriginal('ended_at');
        $newEndedAt = $session->ended_at;

        if ($oldEndedAt === null) {
            return;
        }

        if ($newEndedAt === null) {
            return;
        }

        if (is_string($oldEndedAt)) {
            $oldEndedAt = Carbon::parse($oldEndedAt);
        }

        // Update sign-ins where signed_out_at matches the old ended_at
        // This keeps them in sync when the session end time changes
        $session->signIns()
            ->whereNotNull('signed_out_at')
            ->where('signed_out_at', $oldEndedAt)
            ->update([
                'signed_out_at' => $newEndedAt,
            ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Delegate;
use App\Models\User;

final class TerminateDelegate
{
    /**
     * End the delegate's mandate when the actor is allowed to.
     *
     * The mandate ends now. A mandate that has not started yet ends at its start instant: it
     * becomes zero-length, so it is never active (periods are half-open).
     *
     * @return bool Whether the mandate was ended.
     */
    public function handle(User $actor, ?Delegate $delegate): bool
    {
        if ($delegate === null || ! $actor->can('terminate', $delegate)) {
            return false;
        }

        return $delegate->end($delegate->started_at->isFuture() ? $delegate->started_at : now());
    }
}

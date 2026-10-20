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
     * A mandate that has not started yet ends on its start date, since the end date is inclusive.
     *
     * @return bool Whether the mandate was ended.
     */
    public function handle(User $actor, ?Delegate $delegate): bool
    {
        if ($delegate === null || ! $actor->can('terminate', $delegate)) {
            return false;
        }

        return $delegate->end($delegate->started_at->isFuture() ? $delegate->started_at : null);
    }
}

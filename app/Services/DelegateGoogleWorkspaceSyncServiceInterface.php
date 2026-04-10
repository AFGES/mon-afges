<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Delegate;

interface DelegateGoogleWorkspaceSyncServiceInterface
{
    /**
     * Add a single delegate to the Google Workspace group.
     */
    public function addDelegate(Delegate $delegate): bool;

    /**
     * Remove a single delegate from the Google Workspace group.
     */
    public function removeDelegate(Delegate $delegate): bool;
}

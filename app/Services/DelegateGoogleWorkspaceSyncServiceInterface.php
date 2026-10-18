<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GoogleWorkspaceSyncEvent;
use App\Models\Delegate;
use Closure;

interface DelegateGoogleWorkspaceSyncServiceInterface
{
    /**
     * Whether the underlying Google Workspace integration is configured.
     */
    public function isConfigured(): bool;

    /**
     * Whether the delegate is entitled to be in the group: approved, verified email, valid mandate dates.
     */
    public function shouldSync(Delegate $delegate): bool;

    /**
     * Add a single delegate to the Google Workspace group.
     */
    public function addDelegate(Delegate $delegate): bool;

    /**
     * Remove a single delegate from the Google Workspace group.
     */
    public function removeDelegate(Delegate $delegate): bool;

    /**
     * Sync every delegate to the Google Workspace group, removing users who are no longer delegates.
     *
     * @param  (Closure(GoogleWorkspaceSyncEvent, string|int): void)|null  $onProgress  Receives an event and its detail (an email, or the count for FoundActive).
     * @return array{
     *     already_in_group: int,
     *     added: int,
     *     removed: int,
     *     failed_to_add: int,
     *     failed_to_remove: int,
     * }
     */
    public function syncAll(bool $dryRun = false, ?Closure $onProgress = null): array;
}

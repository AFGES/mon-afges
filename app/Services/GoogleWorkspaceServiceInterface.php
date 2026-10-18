<?php

declare(strict_types=1);

namespace App\Services;

interface GoogleWorkspaceServiceInterface
{
    /**
     * Whether this implementation can actually talk to Google Workspace.
     */
    public function isConfigured(): bool;

    /**
     * Add a user to the delegate group.
     */
    public function addToGroup(string $email): bool;

    /**
     * Remove a user from the delegate group.
     */
    public function removeFromGroup(string $email): bool;

    /**
     * Check if a user is in the delegate group.
     */
    public function isInGroup(string $email): bool;
}

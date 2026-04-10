<?php

declare(strict_types=1);

namespace App\Services;

interface GoogleWorkspaceServiceInterface
{
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

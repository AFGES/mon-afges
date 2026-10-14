<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Delegate;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

final class DelegateGoogleWorkspaceSyncService implements DelegateGoogleWorkspaceSyncServiceInterface
{
    public function __construct(
        private GoogleWorkspaceServiceInterface $googleWorkspaceService
    ) {}

    /**
     * Check if a delegate should be synced to Google Workspace.
     */
    public function shouldSync(Delegate $delegate): bool
    {
        if (! $delegate->email) {
            return false;
        }

        if (! $delegate->hasVerifiedEmail()) {
            return false;
        }

        if (! $delegate->isCurrentlyActive()) {
            return false;
        }

        return true;
    }

    /**
     * Check if other active delegates are using the same email.
     */
    public function otherDelegateUsesEmail(Delegate $delegate): bool
    {
        return Delegate::whereEmail($delegate->email)
            ->where('id', '!=', $delegate->id)
            ->whereNotNull('email_verified_at')
            ->exists();
    }

    /**
     * Add a single delegate to the Google Workspace group.
     */
    public function addDelegate(Delegate $delegate): bool
    {
        if (! $this->isGoogleWorkspaceConfigured()) {
            return false;
        }

        if (! $this->shouldSync($delegate)) {
            Log::warning('Cannot sync delegate to Google Workspace: requirements not met', [
                'delegate_id' => $delegate->id,
                'email' => $delegate->email,
                'has_email' => (bool) $delegate->email,
                'email_verified' => $delegate->hasVerifiedEmail(),
                'is_active' => $delegate->isCurrentlyActive(),
            ]);

            return false;
        }

        // Check if this email is already in use by other active delegates
        if ($this->otherDelegateUsesEmail($delegate)) {
            Log::info('Skipping Google Workspace add: email already in use by other active delegates', [
                'delegate_id' => $delegate->id,
                'email' => $delegate->email,
            ]);

            return true; // Return true as this isn't really a failure
        }

        return $this->googleWorkspaceService->addToGroup($delegate->email);
    }

    /**
     * Remove a single delegate from the Google Workspace group.
     */
    public function removeDelegate(Delegate $delegate): bool
    {
        if (! $this->isGoogleWorkspaceConfigured()) {
            return false;
        }

        if (! $delegate->email) {
            Log::warning('Cannot remove delegate from Google Workspace: email missing', [
                'delegate_id' => $delegate->id,
            ]);

            return false;
        }

        // Check if this email is still in use by other active delegates
        if ($this->otherDelegateUsesEmail($delegate)) {
            Log::info('Skipping Google Workspace removal: email still in use by other active delegates', [
                'delegate_id' => $delegate->id,
                'email' => $delegate->email,
            ]);

            return true; // Return true as this isn't really a failure
        }

        return $this->googleWorkspaceService->removeFromGroup($delegate->email);
    }

    /**
     * Get all active delegate emails that should be in the Google Workspace group.
     *
     * @return Collection<int, string>
     */
    public function getActiveDelegateEmails(): Collection
    {
        return Delegate::whereNotNull('email')
            ->whereNotNull('email_verified_at')
            ->pluck('email')
            ->unique();
    }

    /**
     * Check if an email is in the Google Workspace group.
     */
    public function isInGroup(string $email): bool
    {
        return $this->googleWorkspaceService->isInGroup($email);
    }

    /**
     * Add an email to the Google Workspace group.
     */
    public function addToGroup(string $email): bool
    {
        return $this->googleWorkspaceService->addToGroup($email);
    }

    /**
     * Remove an email from the Google Workspace group.
     */
    public function removeFromGroup(string $email): bool
    {
        return $this->googleWorkspaceService->removeFromGroup($email);
    }

    /**
     * Sync all delegates to the Google Workspace group.
     * Returns an array with sync statistics.
     *
     * @return array{
     *     already_in_group: int,
     *     added: int,
     *     removed: int,
     *     failed_to_add: int,
     *     failed_to_remove: int,
     * }
     */
    public function syncAll(bool $dryRun = false): array
    {
        if (! $this->isGoogleWorkspaceConfigured()) {
            Log::warning('Google Workspace configuration incomplete, skipping delegate sync');

            return array_fill_keys(
                ['already_in_group', 'added', 'removed', 'failed_to_add', 'failed_to_remove'],
                0,
            );
        }

        $activeDelegateEmails = $this->getActiveDelegateEmails();

        $stats = [
            'already_in_group' => 0,
            'added' => 0,
            'removed' => 0,
            'failed_to_add' => 0,
            'failed_to_remove' => 0,
        ];

        // Ensure active delegates are in the group
        foreach ($activeDelegateEmails as $email) {
            if ($this->googleWorkspaceService->isInGroup($email)) {
                $stats['already_in_group']++;

                continue;
            }

            if ($dryRun) {
                $stats['added']++;

                continue;
            }

            if ($this->googleWorkspaceService->addToGroup($email)) {
                $stats['added']++;

                continue;
            }

            $stats['failed_to_add']++;
        }

        // Remove users who are no longer active delegates
        $allUserEmails = User::whereNotNull('email')->pluck('email')->toArray();

        foreach ($allUserEmails as $email) {
            if ($activeDelegateEmails->contains($email)) {
                continue;
            }

            if (! $this->googleWorkspaceService->isInGroup($email)) {
                continue;
            }

            if ($dryRun) {
                $stats['removed']++;

                continue;
            }

            if ($this->googleWorkspaceService->removeFromGroup($email)) {
                $stats['removed']++;

                continue;
            }

            $stats['failed_to_remove']++;
        }

        return $stats;
    }

    private function isGoogleWorkspaceConfigured(): bool
    {
        return ! $this->googleWorkspaceService instanceof UnconfiguredGoogleWorkspaceService;
    }
}

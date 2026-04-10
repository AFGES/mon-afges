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
        private ?GoogleWorkspaceServiceInterface $googleWorkspaceService = null
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
        $service = $this->getGoogleWorkspaceService();

        if ($service === null) {
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

        return $service->addToGroup($delegate->email);
    }

    /**
     * Remove a single delegate from the Google Workspace group.
     */
    public function removeDelegate(Delegate $delegate): bool
    {
        $service = $this->getGoogleWorkspaceService();

        if ($service === null) {
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

        return $service->removeFromGroup($delegate->email);
    }

    /**
     * Get all active delegate emails that should be in the Google Workspace group.
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
        $service = $this->getGoogleWorkspaceService();

        if ($service === null) {
            return false;
        }

        return $service->isInGroup($email);
    }

    /**
     * Add an email to the Google Workspace group.
     */
    public function addToGroup(string $email): bool
    {
        $service = $this->getGoogleWorkspaceService();

        if ($service === null) {
            return false;
        }

        return $service->addToGroup($email);
    }

    /**
     * Remove an email from the Google Workspace group.
     */
    public function removeFromGroup(string $email): bool
    {
        $service = $this->getGoogleWorkspaceService();

        if ($service === null) {
            return false;
        }

        return $service->removeFromGroup($email);
    }

    /**
     * Sync all delegates to the Google Workspace group.
     * Returns an array with sync statistics.
     */
    public function syncAll(bool $dryRun = false): array
    {
        $service = $this->getGoogleWorkspaceService();

        if ($service === null) {
            return [
                'already_in_group' => 0,
                'added' => 0,
                'removed' => 0,
                'failed_to_add' => 0,
                'failed_to_remove' => 0,
            ];
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
            if ($service->isInGroup($email)) {
                $stats['already_in_group']++;
            } else {
                if (! $dryRun && $service->addToGroup($email)) {
                    $stats['added']++;
                } elseif (! $dryRun) {
                    $stats['failed_to_add']++;
                } else {
                    $stats['added']++; // Count as added in dry run
                }
            }
        }

        // Remove users who are no longer active delegates
        $allUserEmails = User::whereNotNull('email')->pluck('email')->toArray();

        foreach ($allUserEmails as $email) {
            // Skip if this user is an active delegate
            if ($activeDelegateEmails->contains($email)) {
                continue;
            }

            // Check if they're in the group
            if ($service->isInGroup($email)) {
                if (! $dryRun && $service->removeFromGroup($email)) {
                    $stats['removed']++;
                } elseif (! $dryRun) {
                    $stats['failed_to_remove']++;
                } else {
                    $stats['removed']++; // Count as removed in dry run
                }
            }
        }

        return $stats;
    }

    /**
     * Get or create the Google Workspace service instance.
     */
    private function getGoogleWorkspaceService(): ?GoogleWorkspaceServiceInterface
    {
        if ($this->googleWorkspaceService !== null) {
            return $this->googleWorkspaceService;
        }

        $serviceAccountJson = config('services.google.workspace.service_account_json');
        $adminEmail = config('services.google.workspace.admin_email');
        $delegateGroup = config('services.google.workspace.delegate_group');

        // If any required config is missing, disable sync
        if (! $serviceAccountJson || ! $adminEmail || ! $delegateGroup) {
            Log::warning('Google Workspace configuration incomplete, skipping delegate sync');

            return null;
        }

        $this->googleWorkspaceService = new GoogleWorkspaceService(
            $serviceAccountJson,
            $adminEmail,
            $delegateGroup
        );

        return $this->googleWorkspaceService;
    }
}

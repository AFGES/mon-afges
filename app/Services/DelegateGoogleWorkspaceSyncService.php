<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GoogleWorkspaceSyncEvent;
use App\Models\Delegate;
use App\Models\User;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

final class DelegateGoogleWorkspaceSyncService implements DelegateGoogleWorkspaceSyncServiceInterface
{
    public function __construct(
        private GoogleWorkspaceServiceInterface $googleWorkspaceService
    ) {}

    public function isConfigured(): bool
    {
        return $this->googleWorkspaceService->isConfigured();
    }

    /**
     * A delegate belongs to the Google Workspace group only once approved, with a verified email,
     * and while its mandate is valid (started, and not ended before today).
     */
    public function shouldSync(Delegate $delegate): bool
    {
        if (! $delegate->email) {
            return false;
        }

        if (! $delegate->hasVerifiedEmail()) {
            return false;
        }

        if (! $delegate->isApproved()) {
            return false;
        }

        return $delegate->isCurrentlyActive();
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
        if (! $this->isConfigured()) {
            return false;
        }

        if (! $this->shouldSync($delegate)) {
            Log::warning('Cannot sync delegate to Google Workspace: requirements not met', [
                'delegate_id' => $delegate->id,
                'email' => $delegate->email,
                'has_email' => (bool) $delegate->email,
                'email_verified' => $delegate->hasVerifiedEmail(),
                'is_approved' => $delegate->isApproved(),
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
        if (! $this->isConfigured()) {
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
     * Get all delegate emails that should be in the Google Workspace group.
     * Relies on the Delegate global scopes: approved and currently active mandates only.
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
     * Sync all delegates to the Google Workspace group.
     * Returns an array with sync statistics.
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
    public function syncAll(bool $dryRun = false, ?Closure $onProgress = null): array
    {
        if (! $this->isConfigured()) {
            Log::warning('Google Workspace configuration incomplete, skipping delegate sync');

            return array_fill_keys(
                ['already_in_group', 'added', 'removed', 'failed_to_add', 'failed_to_remove'],
                0,
            );
        }

        $activeDelegateEmails = $this->getActiveDelegateEmails();

        $report = $onProgress ?? static fn (GoogleWorkspaceSyncEvent $event, string|int $detail): null => null;
        $report(GoogleWorkspaceSyncEvent::FoundActive, $activeDelegateEmails->count());

        $stats = [
            'already_in_group' => 0,
            'added' => 0,
            'removed' => 0,
            'failed_to_add' => 0,
            'failed_to_remove' => 0,
        ];

        foreach ($activeDelegateEmails as $email) {
            if ($this->googleWorkspaceService->isInGroup($email)) {
                $stats['already_in_group']++;
                $report(GoogleWorkspaceSyncEvent::AlreadyInGroup, $email);

                continue;
            }

            if ($dryRun) {
                $stats['added']++;
                $report(GoogleWorkspaceSyncEvent::Added, $email);

                continue;
            }

            if ($this->googleWorkspaceService->addToGroup($email)) {
                $stats['added']++;
                $report(GoogleWorkspaceSyncEvent::Added, $email);

                continue;
            }

            $stats['failed_to_add']++;
            $report(GoogleWorkspaceSyncEvent::FailedToAdd, $email);
        }

        $report(GoogleWorkspaceSyncEvent::CheckingRemovals, 0);

        foreach ($this->getRemovalCandidateEmails() as $email) {
            if ($activeDelegateEmails->contains($email)) {
                continue;
            }

            if (! $this->googleWorkspaceService->isInGroup($email)) {
                continue;
            }

            if ($dryRun) {
                $stats['removed']++;
                $report(GoogleWorkspaceSyncEvent::Removed, $email);

                continue;
            }

            if ($this->googleWorkspaceService->removeFromGroup($email)) {
                $stats['removed']++;
                $report(GoogleWorkspaceSyncEvent::Removed, $email);

                continue;
            }

            $stats['failed_to_remove']++;
            $report(GoogleWorkspaceSyncEvent::FailedToRemove, $email);
        }

        return $stats;
    }

    /**
     * Every email that may sit in the group without being entitled to it: user account emails and
     * the convocation emails of all delegates, whatever their approval status, dates or deletion state.
     *
     * @return Collection<int, string>
     */
    private function getRemovalCandidateEmails(): Collection
    {
        return Delegate::withoutGlobalScopes()
            ->withTrashed()
            ->whereNotNull('email')
            ->pluck('email')
            ->merge(User::whereNotNull('email')->pluck('email'))
            ->unique()
            ->values();
    }
}

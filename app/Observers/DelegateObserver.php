<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Delegate;
use App\Models\User;
use App\Notifications\DelegateApprovalRequested;
use App\Services\GoogleWorkspaceService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

final class DelegateObserver
{
    private ?GoogleWorkspaceService $googleWorkspaceService = null;

    /**
     * Get or create the Google Workspace service instance.
     */
    private function getGoogleWorkspaceService(): ?GoogleWorkspaceService
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

    /**
     * Handle the Delegate "created" event.
     */
    public function created(Delegate $delegate): void
    {
        $this->notifyApprovers($delegate);
        $this->addToGroup($delegate);
    }

    /**
     * Handle the Delegate "updated" event.
     */
    public function updated(Delegate $delegate): void
    {
        // If the delegate's end date was set, remove them from the group
        if ($delegate->wasChanged('ended_at') && $delegate->ended_at !== null) {
            $this->removeFromGroup($delegate);
        }
    }

    /**
     * Handle the Delegate "deleted" event.
     */
    public function deleted(Delegate $delegate): void
    {
        $this->removeFromGroup($delegate);
    }

    /**
     * Handle the Delegate "restored" event.
     */
    public function restored(Delegate $delegate): void
    {
        $this->addToGroup($delegate);
    }

    /**
     * Notify users with approval permissions about the new delegate application.
     */
    private function notifyApprovers(Delegate $delegate): void
    {
        try {
            $approvers = User::permission('Update:Delegate')->get();

            if ($approvers->isEmpty()) {
                return;
            }

            Notification::send($approvers, new DelegateApprovalRequested($delegate));
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
            // Permission doesn't exist yet, skip notification
            return;
        }
    }

    /**
     * Add delegate to Google Workspace group.
     */
    private function addToGroup(Delegate $delegate): void
    {
        $service = $this->getGoogleWorkspaceService();

        if ($service === null) {
            return;
        }

        $user = $delegate->user;

        if (! $user || ! $user->email) {
            Log::warning('Cannot sync delegate to Google Workspace: user or email missing', [
                'delegate_id' => $delegate->id,
            ]);

            return;
        }

        // Only add if delegate is currently active (no end date or future end date)
        if ($delegate->ended_at === null || $delegate->ended_at->isFuture()) {
            $service->addToGroup($user->email);
        }
    }

    /**
     * Remove delegate from Google Workspace group.
     */
    private function removeFromGroup(Delegate $delegate): void
    {
        $service = $this->getGoogleWorkspaceService();

        if ($service === null) {
            return;
        }

        $user = $delegate->user;

        if (! $user || ! $user->email) {
            Log::warning('Cannot remove delegate from Google Workspace: user or email missing', [
                'delegate_id' => $delegate->id,
            ]);

            return;
        }

        $service->removeFromGroup($user->email);
    }
}

<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Delegate;
use App\Models\User;
use App\Notifications\DelegateApprovalRequested;
use App\Services\DelegateGoogleWorkspaceSyncService;
use Illuminate\Support\Facades\Notification;

final class DelegateObserver
{
    public function __construct(
        private ?DelegateGoogleWorkspaceSyncService $syncService = null
    ) {}

    /**
     * Get or create the sync service instance.
     */
    private function getSyncService(): DelegateGoogleWorkspaceSyncService
    {
        if ($this->syncService !== null) {
            return $this->syncService;
        }

        $this->syncService = new DelegateGoogleWorkspaceSyncService;

        return $this->syncService;
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
        $this->getSyncService()->addDelegate($delegate);
    }

    /**
     * Remove delegate from Google Workspace group.
     */
    private function removeFromGroup(Delegate $delegate): void
    {
        $this->getSyncService()->removeDelegate($delegate);
    }
}

<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Delegate;
use App\Models\User;
use App\Notifications\DelegateApprovalRequestedNotification;
use App\Services\DelegateGoogleWorkspaceSyncService;
use App\Services\DelegateGoogleWorkspaceSyncServiceInterface;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

final class DelegateObserver
{
    public function __construct(
        private ?DelegateGoogleWorkspaceSyncServiceInterface $syncService = null
    ) {}

    public function created(Delegate $delegate): void
    {
        $this->notifyApprovers($delegate);
        $this->addToGroup($delegate);
    }

    public function updated(Delegate $delegate): void
    {
        if (! $delegate->wasChanged('ended_at')) {
            return;
        }

        if ($delegate->ended_at === null) {
            return;
        }

        $this->removeFromGroup($delegate);
    }

    public function deleted(Delegate $delegate): void
    {
        $this->removeFromGroup($delegate);
    }

    public function restored(Delegate $delegate): void
    {
        $this->addToGroup($delegate);
    }

    /**
     * Get or create the sync service instance.
     */
    private function getSyncService(): DelegateGoogleWorkspaceSyncServiceInterface
    {
        if ($this->syncService !== null) {
            return $this->syncService;
        }

        $this->syncService = new DelegateGoogleWorkspaceSyncService;

        return $this->syncService;
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

            Notification::send($approvers, new DelegateApprovalRequestedNotification($delegate));
        } catch (PermissionDoesNotExist) {
            // Permission doesn't exist yet, skip notification
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

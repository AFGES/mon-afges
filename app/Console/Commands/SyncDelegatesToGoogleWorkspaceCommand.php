<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\DelegateGoogleWorkspaceSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class SyncDelegatesToGoogleWorkspaceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delegates:sync-google-workspace {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync delegates to Google Workspace group, removing users who are no longer delegates';

    public function handle(DelegateGoogleWorkspaceSyncService $syncService): int
    {
        $serviceAccountJson = config('services.google.workspace.service_account_json');
        $adminEmail = config('services.google.workspace.admin_email');
        $delegateGroup = config('services.google.workspace.delegate_group');

        if (! $serviceAccountJson || ! $adminEmail || ! $delegateGroup) {
            $this->error('Google Workspace configuration is incomplete. Please set all required environment variables.');
            $this->info('Required: GOOGLE_WORKSPACE_SERVICE_ACCOUNT_JSON, GOOGLE_WORKSPACE_ADMIN_EMAIL, GOOGLE_WORKSPACE_DELEGATE_GROUP');

            return self::FAILURE;
        }

        $isDryRun = $this->option('dry-run');
        if ($isDryRun) {
            $this->info('Running in dry-run mode - no changes will be made');
        }

        $activeDelegateEmails = $syncService->getActiveDelegateEmails();

        $this->info("Found {$activeDelegateEmails->count()} active delegate(s)");

        $alreadyInCount = 0;
        $addedCount = 0;
        $failedToAddCount = 0;
        $removedCount = 0;
        $failedToRemoveCount = 0;

        $this->info('Ensuring active delegates are in the group...');
        $this->newLine();

        foreach ($activeDelegateEmails as $email) {
            if ($syncService->isInGroup($email)) {
                $this->line("  ✓ {$email} - already in group");
                $alreadyInCount++;

                continue;
            }

            if ($isDryRun) {
                $this->line("  [DRY RUN] Would add {$email}");
                $addedCount++;

                continue;
            }

            if ($syncService->addToGroup($email)) {
                $this->line("  + {$email} - added to group");
                $addedCount++;

                continue;
            }

            $this->line("  ✗ {$email} - failed to add");
            $failedToAddCount++;
        }

        $this->newLine();
        $this->info('Checking for users to remove from the group...');
        $this->newLine();

        $allUserEmails = User::whereNotNull('email')->get('email');

        foreach ($allUserEmails as $userEmail) {
            $email = $userEmail->email;
            if ($activeDelegateEmails->contains($email)) {
                continue;
            }

            if (! $syncService->isInGroup($email)) {
                continue;
            }

            if ($isDryRun) {
                $this->line("  [DRY RUN] Would remove {$email}");
                $removedCount++;

                continue;
            }

            if ($syncService->removeFromGroup($email)) {
                $this->line("  - {$email} - removed from group");
                $removedCount++;

                continue;
            }

            $this->line("  ✗ {$email} - failed to remove");
            $failedToRemoveCount++;
        }

        $this->newLine();
        $this->info('Synchronization complete!');
        $this->table(
            ['Action', 'Count'],
            [
                ['Already in group', $alreadyInCount],
                ['Added to group', $addedCount],
                ['Removed from group', $removedCount],
                ['Failed to add', $failedToAddCount],
                ['Failed to remove', $failedToRemoveCount],
            ]
        );

        if ($isDryRun) {
            $this->info('This was a dry run - no actual changes were made.');
        }

        Log::info('Google Workspace delegate sync completed', [
            'already_in_group' => $alreadyInCount,
            'added' => $addedCount,
            'removed' => $removedCount,
            'failed_to_add' => $failedToAddCount,
            'failed_to_remove' => $failedToRemoveCount,
            'dry_run' => $isDryRun,
        ]);

        return self::SUCCESS;
    }
}

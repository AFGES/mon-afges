<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Delegate;
use App\Models\User;
use App\Services\GoogleWorkspaceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class SyncDelegatesToGoogleWorkspace extends Command
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

    /**
     * Execute the console command.
     */
    public function handle(): int
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

        // Use bound mock if available, otherwise create new service
        $service = app()->bound(GoogleWorkspaceService::class)
            ? app(GoogleWorkspaceService::class)
            : new GoogleWorkspaceService($serviceAccountJson, $adminEmail, $delegateGroup);

        // Get all current active delegates
        $activeDelegateEmails = Delegate::with('user')
            ->get()
            ->pluck('user.email')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $this->info('Found '.count($activeDelegateEmails).' active delegate(s)');

        // Get all users who should be in the group (active delegates)
        $addedCount = 0;
        $alreadyInCount = 0;
        $failedToAddCount = 0;

        $this->info('Ensuring active delegates are in the group...');
        $this->newLine();

        foreach ($activeDelegateEmails as $email) {
            if ($service->isInGroup($email)) {
                $this->line("  ✓ {$email} - already in group");
                $alreadyInCount++;
            } else {
                if ($isDryRun) {
                    $this->line("  [DRY RUN] Would add {$email}");
                    $addedCount++;
                } elseif ($service->addToGroup($email)) {
                    $this->line("  + {$email} - added to group");
                    $addedCount++;
                } else {
                    $this->line("  ✗ {$email} - failed to add");
                    $failedToAddCount++;
                }
            }
        }

        // Find users who are in the group but are not active delegates
        $this->newLine();
        $this->info('Checking for users to remove from the group...');
        $this->newLine();

        $removedCount = 0;
        $failedToRemoveCount = 0;

        // Get all users with email addresses
        $allUserEmails = User::whereNotNull('email')->pluck('email')->toArray();

        foreach ($allUserEmails as $email) {
            // Skip if this user is an active delegate
            if (in_array($email, $activeDelegateEmails)) {
                continue;
            }

            // Check if they're in the group
            if ($service->isInGroup($email)) {
                if ($isDryRun) {
                    $this->line("  [DRY RUN] Would remove {$email}");
                    $removedCount++;
                } elseif ($service->removeFromGroup($email)) {
                    $this->line("  - {$email} - removed from group");
                    $removedCount++;
                } else {
                    $this->line("  ✗ {$email} - failed to remove");
                    $failedToRemoveCount++;
                }
            }
        }

        // Summary
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

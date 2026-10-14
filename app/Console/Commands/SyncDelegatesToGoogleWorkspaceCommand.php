<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\DelegateGoogleWorkspaceSyncServiceInterface;
use App\Services\GoogleWorkspaceService;
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

    public function handle(DelegateGoogleWorkspaceSyncServiceInterface $syncService): int
    {
        if (! GoogleWorkspaceService::isConfigured()) {
            $this->error('Google Workspace configuration is incomplete. Please set all required environment variables.');
            $this->info('Required: GOOGLE_WORKSPACE_SERVICE_ACCOUNT_JSON, GOOGLE_WORKSPACE_ADMIN_EMAIL, GOOGLE_WORKSPACE_DELEGATE_GROUP');

            return self::FAILURE;
        }

        $isDryRun = $this->option('dry-run');
        if ($isDryRun) {
            $this->info('Running in dry-run mode - no changes will be made');
        }

        $stats = $syncService->syncAll($isDryRun, function (string $event, string|int $detail) use ($isDryRun): void {
            $this->reportProgress($event, $detail, $isDryRun);
        });

        $this->newLine();
        $this->info('Synchronization complete!');
        $this->table(
            ['Action', 'Count'],
            [
                ['Already in group', $stats['already_in_group']],
                ['Added to group', $stats['added']],
                ['Removed from group', $stats['removed']],
                ['Failed to add', $stats['failed_to_add']],
                ['Failed to remove', $stats['failed_to_remove']],
            ]
        );

        if ($isDryRun) {
            $this->info('This was a dry run - no actual changes were made.');
        }

        Log::info('Google Workspace delegate sync completed', [...$stats, 'dry_run' => $isDryRun]);

        return self::SUCCESS;
    }

    private function reportProgress(string $event, string|int $detail, bool $isDryRun): void
    {
        match ($event) {
            'found_active' => $this->reportActiveDelegates((int) $detail),
            'checking_removals' => $this->reportRemovalCheck(),
            'already_in_group' => $this->line("  ✓ {$detail} - already in group"),
            'added' => $this->line($isDryRun ? "  [DRY RUN] Would add {$detail}" : "  + {$detail} - added to group"),
            'failed_to_add' => $this->line("  ✗ {$detail} - failed to add"),
            'removed' => $this->line($isDryRun ? "  [DRY RUN] Would remove {$detail}" : "  - {$detail} - removed from group"),
            'failed_to_remove' => $this->line("  ✗ {$detail} - failed to remove"),
            default => null,
        };
    }

    private function reportActiveDelegates(int $count): void
    {
        $this->info("Found {$count} active delegate(s)");
        $this->info('Ensuring active delegates are in the group...');
        $this->newLine();
    }

    private function reportRemovalCheck(): void
    {
        $this->newLine();
        $this->info('Checking for users to remove from the group...');
        $this->newLine();
    }
}

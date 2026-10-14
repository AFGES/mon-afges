<?php

declare(strict_types=1);

use App\Models\Delegate;
use App\Models\User;
use App\Services\GoogleWorkspaceServiceInterface;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;

beforeEach(function () {
    config([
        'services.google.workspace.service_account_json' => '{"type":"service_account"}',
        'services.google.workspace.admin_email' => 'admin@afges.org',
        'services.google.workspace.delegate_group' => 'test-mon@afges.org',
    ]);
});

/**
 * Creating a delegate makes the observer call Google, so the creation runs against a
 * throwaway spy; the test then binds a fresh mock that only records what the command does.
 */
function activeDelegateWithEmail(string $email): Delegate
{
    test()->spy(GoogleWorkspaceServiceInterface::class);

    return Delegate::factory()->approved()->verified()->create(['email' => $email, 'ended_at' => null]);
}

function mockedGoogleWorkspaceForCommand(): MockInterface
{
    return test()->mock(GoogleWorkspaceServiceInterface::class);
}

/**
 * @return array<int, array{0: string, 1: int}>
 */
function syncSummary(int $alreadyInGroup = 0, int $added = 0, int $removed = 0, int $failedToAdd = 0, int $failedToRemove = 0): array
{
    return [
        ['Already in group', $alreadyInGroup],
        ['Added to group', $added],
        ['Removed from group', $removed],
        ['Failed to add', $failedToAdd],
        ['Failed to remove', $failedToRemove],
    ];
}

it('fails without calling Google when the configuration is incomplete', function (string $missingKey) {
    config(["services.google.workspace.{$missingKey}" => null]);
    $this->mock(GoogleWorkspaceServiceInterface::class)->shouldNotReceive('isInGroup', 'addToGroup', 'removeFromGroup');

    $this->artisan('delegates:sync-google-workspace')
        ->expectsOutput('Google Workspace configuration is incomplete. Please set all required environment variables.')
        ->assertFailed();
})->with(['service_account_json', 'admin_email', 'delegate_group']);

it('adds active delegates missing from the group', function () {
    Log::spy();
    activeDelegateWithEmail('delegate@afges.org');
    $google = mockedGoogleWorkspaceForCommand();
    $google->shouldReceive('isInGroup')->andReturnFalse();
    $google->shouldReceive('addToGroup')->once()->with('delegate@afges.org')->andReturnTrue();

    $this->artisan('delegates:sync-google-workspace')
        ->expectsOutputToContain('Found 1 active delegate(s)')
        ->expectsOutputToContain('delegate@afges.org - added to group')
        ->expectsTable(['Action', 'Count'], syncSummary(added: 1))
        ->assertSuccessful();

    Log::shouldHaveReceived('info')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => $message === 'Google Workspace delegate sync completed'
            && $context['added'] === 1
            && $context['dry_run'] === false);
});

it('leaves active delegates that are already in the group', function () {
    activeDelegateWithEmail('existing@afges.org');
    $google = mockedGoogleWorkspaceForCommand();
    $google->shouldReceive('isInGroup')->with('existing@afges.org')->andReturnTrue();
    $google->shouldReceive('isInGroup')->andReturnFalse();
    $google->shouldNotReceive('addToGroup');

    $this->artisan('delegates:sync-google-workspace')
        ->expectsOutputToContain('existing@afges.org - already in group')
        ->expectsTable(['Action', 'Count'], syncSummary(alreadyInGroup: 1))
        ->assertSuccessful();
});

it('reports delegates that could not be added', function () {
    activeDelegateWithEmail('delegate@afges.org');
    $google = mockedGoogleWorkspaceForCommand();
    $google->shouldReceive('isInGroup')->andReturnFalse();
    $google->shouldReceive('addToGroup')->once()->with('delegate@afges.org')->andReturnFalse();

    $this->artisan('delegates:sync-google-workspace')
        ->expectsOutputToContain('delegate@afges.org - failed to add')
        ->expectsTable(['Action', 'Count'], syncSummary(failedToAdd: 1))
        ->assertSuccessful();
});

it('removes users who are in the group without being active delegates', function () {
    User::factory()->create(['email' => 'notdelegate@afges.org']);
    $google = mockedGoogleWorkspaceForCommand();
    $google->shouldReceive('isInGroup')->with('notdelegate@afges.org')->andReturnTrue();
    $google->shouldReceive('removeFromGroup')->once()->with('notdelegate@afges.org')->andReturnTrue();

    $this->artisan('delegates:sync-google-workspace')
        ->expectsOutputToContain('notdelegate@afges.org - removed from group')
        ->expectsTable(['Action', 'Count'], syncSummary(removed: 1))
        ->assertSuccessful();
});

it('reports users who could not be removed', function () {
    User::factory()->create(['email' => 'notdelegate@afges.org']);
    $google = mockedGoogleWorkspaceForCommand();
    $google->shouldReceive('isInGroup')->with('notdelegate@afges.org')->andReturnTrue();
    $google->shouldReceive('removeFromGroup')->once()->with('notdelegate@afges.org')->andReturnFalse();

    $this->artisan('delegates:sync-google-workspace')
        ->expectsOutputToContain('notdelegate@afges.org - failed to remove')
        ->expectsTable(['Action', 'Count'], syncSummary(failedToRemove: 1))
        ->assertSuccessful();
});

it('reports the planned changes without applying them in dry-run mode', function () {
    activeDelegateWithEmail('delegate@afges.org');
    User::factory()->create(['email' => 'notdelegate@afges.org']);
    $google = mockedGoogleWorkspaceForCommand();
    $google->shouldReceive('isInGroup')->with('notdelegate@afges.org')->andReturnTrue();
    $google->shouldReceive('isInGroup')->andReturnFalse();
    $google->shouldNotReceive('addToGroup');
    $google->shouldNotReceive('removeFromGroup');

    $this->artisan('delegates:sync-google-workspace --dry-run')
        ->expectsOutputToContain('Running in dry-run mode')
        ->expectsOutputToContain('[DRY RUN] Would add delegate@afges.org')
        ->expectsOutputToContain('[DRY RUN] Would remove notdelegate@afges.org')
        ->expectsTable(['Action', 'Count'], syncSummary(added: 1, removed: 1))
        ->expectsOutputToContain('This was a dry run - no actual changes were made')
        ->assertSuccessful();
});

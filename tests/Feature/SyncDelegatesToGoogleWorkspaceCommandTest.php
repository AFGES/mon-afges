<?php

use App\Models\Delegate;
use App\Models\User;
use App\Services\DelegateGoogleWorkspaceSyncService;
use App\Services\GoogleWorkspaceService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Mockery\MockInterface;

beforeEach(function () {
    Config::set('services.google.workspace.service_account_json', '{"type":"service_account"}');
    Config::set('services.google.workspace.admin_email', 'admin@afges.org');
    Config::set('services.google.workspace.delegate_group', 'test-mon@afges.org');
});

test('sync command fails when configuration is incomplete', function () {
    Config::set('services.google.workspace.service_account_json', null);

    $this->artisan('delegates:sync-google-workspace')
        ->assertFailed()
        ->expectsOutput('Google Workspace configuration is incomplete. Please set all required environment variables.');
});

test('sync command shows active delegates', function () {
    Delegate::factory()->approved()->create([
        'email' => 'delegate@afges.org',
        'email_verified_at' => now(),
        'started_at' => now(),
        'ended_at' => null,
    ]);

    $mockWorkspaceService = mock(GoogleWorkspaceService::class, function (MockInterface $mock) {
        $mock->shouldReceive('isInGroup')->with('delegate@afges.org')->andReturn(false);
        $mock->shouldReceive('isInGroup')->andReturn(false);
        $mock->shouldReceive('addToGroup')->with('delegate@afges.org')->once()->andReturn(true);
    });

    $mockSyncService = new DelegateGoogleWorkspaceSyncService($mockWorkspaceService);
    app()->instance(DelegateGoogleWorkspaceSyncService::class, $mockSyncService);

    $this->artisan('delegates:sync-google-workspace')
        ->assertSuccessful()
        ->expectsOutputToContain('Found 1 active delegate(s)')
        ->expectsOutputToContain('added to group');
});

test('sync command works in dry run mode', function () {
    Delegate::factory()->approved()->create([
        'email' => 'delegate@afges.org',
        'email_verified_at' => now(),
        'started_at' => now(),
        'ended_at' => null,
    ]);

    $mockWorkspaceService = mock(GoogleWorkspaceService::class, function (MockInterface $mock) {
        $mock->shouldReceive('isInGroup')->andReturn(false);
        $mock->shouldNotReceive('addToGroup');
        $mock->shouldNotReceive('removeFromGroup');
    });

    $mockSyncService = new DelegateGoogleWorkspaceSyncService($mockWorkspaceService);
    app()->instance(DelegateGoogleWorkspaceSyncService::class, $mockSyncService);

    $this->artisan('delegates:sync-google-workspace --dry-run')
        ->assertSuccessful()
        ->expectsOutputToContain('Running in dry-run mode')
        ->expectsOutputToContain('[DRY RUN] Would add delegate@afges.org')
        ->expectsOutputToContain('This was a dry run - no actual changes were made');
});

test('sync command has proper signature and description', function () {
    $command = Artisan::all()['delegates:sync-google-workspace'];

    expect($command->getName())->toBe('delegates:sync-google-workspace')
        ->and($command->getDescription())->toContain('Sync delegates to Google Workspace');
});

test('sync command displays summary table', function () {
    $mockWorkspaceService = mock(GoogleWorkspaceService::class, function (MockInterface $mock) {
        $mock->shouldReceive('isInGroup')->andReturn(false);
    });

    $mockSyncService = new DelegateGoogleWorkspaceSyncService($mockWorkspaceService);
    app()->instance(DelegateGoogleWorkspaceSyncService::class, $mockSyncService);

    $this->artisan('delegates:sync-google-workspace')
        ->assertSuccessful()
        ->expectsOutputToContain('Synchronization complete!')
        ->expectsOutputToContain('Action');
});

test('sync command identifies users to remove from group', function () {
    // Create a user who is not a delegate
    $nonDelegate = User::factory()->create(['email' => 'notdelegate@afges.org']);

    $mockWorkspaceService = mock(GoogleWorkspaceService::class, function (MockInterface $mock) {
        $mock->shouldReceive('isInGroup')
            ->with('notdelegate@afges.org')
            ->andReturn(true);
        $mock->shouldReceive('isInGroup')
            ->andReturn(false);
        $mock->shouldReceive('removeFromGroup')
            ->with('notdelegate@afges.org')
            ->once()
            ->andReturn(true);
    });

    $mockSyncService = new DelegateGoogleWorkspaceSyncService($mockWorkspaceService);
    app()->instance(DelegateGoogleWorkspaceSyncService::class, $mockSyncService);

    $this->artisan('delegates:sync-google-workspace')
        ->assertSuccessful()
        ->expectsOutputToContain('removed from group');
});

test('sync command handles users already in group', function () {
    $user = User::factory()->create();
    Delegate::factory()->approved()->create([
        'user_id' => $user->id,
        'email' => 'existing@afges.org',
        'email_verified_at' => now(),
        'started_at' => now(),
        'ended_at' => null,
    ]);

    $mockWorkspaceService = mock(GoogleWorkspaceService::class, function (MockInterface $mock) {
        // More specific expectation must come first
        $mock->shouldReceive('isInGroup')
            ->with('existing@afges.org')
            ->andReturn(true);
        // Catch-all for other emails
        $mock->shouldReceive('isInGroup')
            ->andReturn(false);
        $mock->shouldNotReceive('addToGroup');
    });

    $mockSyncService = new DelegateGoogleWorkspaceSyncService($mockWorkspaceService);
    app()->instance(DelegateGoogleWorkspaceSyncService::class, $mockSyncService);

    $this->artisan('delegates:sync-google-workspace')
        ->assertSuccessful()
        ->expectsOutputToContain('already in group');
});

test('sync command handles API failures gracefully', function () {
    $user = User::factory()->create();
    Delegate::factory()->approved()->create([
        'user_id' => $user->id,
        'email' => 'delegate@afges.org',
        'email_verified_at' => now(),
        'started_at' => now(),
        'ended_at' => null,
    ]);

    $mockWorkspaceService = mock(GoogleWorkspaceService::class, function (MockInterface $mock) {
        $mock->shouldReceive('isInGroup')->andReturn(false);
        $mock->shouldReceive('addToGroup')->with('delegate@afges.org')->once()->andReturn(false);
    });

    $mockSyncService = new DelegateGoogleWorkspaceSyncService($mockWorkspaceService);
    app()->instance(DelegateGoogleWorkspaceSyncService::class, $mockSyncService);

    $this->artisan('delegates:sync-google-workspace')
        ->assertSuccessful()
        ->expectsOutputToContain('failed to add');
});

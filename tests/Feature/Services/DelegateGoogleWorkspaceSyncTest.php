<?php

declare(strict_types=1);

use App\Models\Delegate;
use App\Models\User;
use App\Observers\DelegateObserver;
use App\Services\DelegateGoogleWorkspaceSyncService;
use App\Services\DelegateGoogleWorkspaceSyncServiceInterface;
use App\Services\GoogleWorkspaceServiceInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    // Disable observer for most tests
    Delegate::unsetEventDispatcher();
});

test('delegate observer adds user to google workspace group when created', function () {
    Config::set('services.google.workspace.service_account_json', '{"type":"service_account"}');
    Config::set('services.google.workspace.admin_email', 'admin@afges.org');
    Config::set('services.google.workspace.delegate_group', 'test-mon@afges.org');

    $mockSyncService = mock(DelegateGoogleWorkspaceSyncServiceInterface::class);
    $mockSyncService->shouldReceive('addDelegate')
        ->once()
        ->andReturn(true);

    $observer = new DelegateObserver($mockSyncService);

    $delegate = Delegate::factory()->make([
        'email' => 'delegate@afges.org',
        'email_verified_at' => now(),
        'started_at' => now(),
        'ended_at' => null,
    ]);

    $observer->created($delegate);
});

test('delegate observer removes user from google workspace group when ended', function () {
    Config::set('services.google.workspace.service_account_json', '{"type":"service_account"}');
    Config::set('services.google.workspace.admin_email', 'admin@afges.org');
    Config::set('services.google.workspace.delegate_group', 'test-mon@afges.org');

    $delegate = Delegate::factory()->create([
        'email' => 'delegate@afges.org',
        'started_at' => now()->subDays(10),
        'ended_at' => null,
    ]);

    // Now update the delegate to mark it as ended
    $delegate->ended_at = now();
    $delegate->syncChanges(); // Mark as changed

    $mockSyncService = mock(DelegateGoogleWorkspaceSyncServiceInterface::class);
    $mockSyncService->shouldReceive('removeDelegate')
        ->once()
        ->andReturn(true);

    $observer = new DelegateObserver($mockSyncService);

    $observer->updated($delegate);
});

test('delegate observer removes user from google workspace group when deleted', function () {
    Config::set('services.google.workspace.service_account_json', '{"type":"service_account"}');
    Config::set('services.google.workspace.admin_email', 'admin@afges.org');
    Config::set('services.google.workspace.delegate_group', 'test-mon@afges.org');

    $user = User::factory()->create();
    $delegate = Delegate::factory()->make([
        'user_id' => $user->id,
        'email' => 'delegate@afges.org',
        'started_at' => now()->subDays(10),
        'ended_at' => null,
    ]);

    $mockSyncService = mock(DelegateGoogleWorkspaceSyncServiceInterface::class);
    $mockSyncService->shouldReceive('removeDelegate')
        ->once()
        ->andReturn(true);

    $observer = new DelegateObserver($mockSyncService);

    $observer->deleted($delegate);
});

test('delegate observer re-adds user to google workspace group when restored', function () {
    Config::set('services.google.workspace.service_account_json', '{"type":"service_account"}');
    Config::set('services.google.workspace.admin_email', 'admin@afges.org');
    Config::set('services.google.workspace.delegate_group', 'test-mon@afges.org');

    $user = User::factory()->create();
    $delegate = Delegate::factory()->make([
        'user_id' => $user->id,
        'email' => 'delegate@afges.org',
        'email_verified_at' => now(),
        'started_at' => now()->subDays(10),
        'ended_at' => null,
    ]);

    $mockSyncService = mock(DelegateGoogleWorkspaceSyncServiceInterface::class);
    $mockSyncService->shouldReceive('addDelegate')
        ->once()
        ->andReturn(true);

    $observer = new DelegateObserver($mockSyncService);

    $observer->restored($delegate);
});

test('delegate sync skips when google workspace config is missing', function () {
    Config::set('services.google.workspace.service_account_json', null);

    Log::shouldReceive('warning')
        ->once()
        ->with('Google Workspace configuration incomplete, skipping delegate sync');

    $observer = new DelegateObserver;
    $user = User::factory()->create(['email' => 'delegate@afges.org']);
    $delegate = Delegate::factory()->make([
        'user_id' => $user->id,
        'started_at' => now(),
        'ended_at' => null,
    ]);

    $observer->created($delegate);
});

test('delegate sync handles delegate without email gracefully', function () {
    Config::set('services.google.workspace.service_account_json', '{"type":"service_account"}');
    Config::set('services.google.workspace.admin_email', 'admin@afges.org');
    Config::set('services.google.workspace.delegate_group', 'test-mon@afges.org');

    Log::shouldReceive('warning')
        ->once()
        ->withArgs(function ($message, $context) {
            return $message === 'Cannot sync delegate to Google Workspace: requirements not met'
                && $context['has_email'] === false;
        });

    $observer = new DelegateObserver;
    $user = User::factory()->create();
    $delegate = Delegate::factory()->make([
        'user_id' => $user->id,
        'email' => null,
        'started_at' => now(),
        'ended_at' => null,
    ]);

    $observer->created($delegate);
});

test('delegate sync skips when email is not verified', function () {
    Config::set('services.google.workspace.service_account_json', '{"type":"service_account"}');
    Config::set('services.google.workspace.admin_email', 'admin@afges.org');
    Config::set('services.google.workspace.delegate_group', 'test-mon@afges.org');

    Log::shouldReceive('warning')
        ->once()
        ->withArgs(function ($message, $context) {
            return $message === 'Cannot sync delegate to Google Workspace: requirements not met'
                && $context['email'] === 'unverified@afges.org'
                && $context['email_verified'] === false;
        });

    $observer = new DelegateObserver;
    $user = User::factory()->create();
    $delegate = Delegate::factory()->make([
        'user_id' => $user->id,
        'email' => 'unverified@afges.org',
        'email_verified_at' => null,
        'started_at' => now(),
        'ended_at' => null,
    ]);

    $observer->created($delegate);
});

test('user has delegates relationship', function () {
    $user = User::factory()->create();

    Delegate::factory()->approved()->count(3)->create([
        'user_id' => $user->id,
    ]);

    expect($user->delegates)->toHaveCount(3)
        ->and($user->delegates->first())->toBeInstanceOf(Delegate::class);
});

test('delegate sync skips adding email when already in use by other active delegates', function () {
    Config::set('services.google.workspace.service_account_json', '{"type":"service_account"}');
    Config::set('services.google.workspace.admin_email', 'admin@afges.org');
    Config::set('services.google.workspace.delegate_group', 'test-mon@afges.org');

    // Create first delegate with verified email
    $existingDelegate = Delegate::factory()->approved()->create([
        'email' => 'shared@afges.org',
        'email_verified_at' => now(),
        'started_at' => now()->subDays(10),
        'ended_at' => null,
    ]);

    // Mock workspace service but not the sync service - let real implementation run
    $mockWorkspaceService = mock(GoogleWorkspaceServiceInterface::class);
    $mockWorkspaceService->shouldNotReceive('addToGroup'); // Should NOT be called

    $syncService = new DelegateGoogleWorkspaceSyncService($mockWorkspaceService);

    Log::shouldReceive('info')
        ->once()
        ->withArgs(function ($message, $context) {
            return $message === 'Skipping Google Workspace add: email already in use by other active delegates'
                && $context['email'] === 'shared@afges.org';
        });

    $observer = new DelegateObserver($syncService);

    // Create second delegate with same email
    $user2 = User::factory()->create();
    $newDelegate = Delegate::factory()->make([
        'user_id' => $user2->id,
        'email' => 'shared@afges.org',
        'email_verified_at' => now(),
        'started_at' => now(),
        'ended_at' => null,
    ]);

    $observer->created($newDelegate);
});

test('delegate sync skips removing email when still in use by other active delegates', function () {
    Config::set('services.google.workspace.service_account_json', '{"type":"service_account"}');
    Config::set('services.google.workspace.admin_email', 'admin@afges.org');
    Config::set('services.google.workspace.delegate_group', 'test-mon@afges.org');

    // Create two delegates with same email
    $user1 = User::factory()->create();
    $delegate1 = Delegate::factory()->approved()->create([
        'user_id' => $user1->id,
        'email' => 'shared@afges.org',
        'email_verified_at' => now(),
        'started_at' => now()->subDays(10),
        'ended_at' => null,
    ]);

    $user2 = User::factory()->create();
    $delegate2 = Delegate::factory()->approved()->create([
        'user_id' => $user2->id,
        'email' => 'shared@afges.org',
        'email_verified_at' => now(),
        'started_at' => now()->subDays(5),
        'ended_at' => null,
    ]);

    // Mock workspace service but not the sync service - let real implementation run
    $mockWorkspaceService = mock(GoogleWorkspaceServiceInterface::class);
    $mockWorkspaceService->shouldNotReceive('removeFromGroup'); // Should NOT be called

    $syncService = new DelegateGoogleWorkspaceSyncService($mockWorkspaceService);

    Log::shouldReceive('info')
        ->once()
        ->withArgs(function ($message, $context) use ($delegate1) {
            return $message === 'Skipping Google Workspace removal: email still in use by other active delegates'
                && $context['delegate_id'] === $delegate1->id
                && $context['email'] === 'shared@afges.org';
        });

    $observer = new DelegateObserver($syncService);

    // Delete first delegate - should NOT remove from group
    $observer->deleted($delegate1);
});

test('delegate sync removes email when last active delegate is deleted', function () {
    Config::set('services.google.workspace.service_account_json', '{"type":"service_account"}');
    Config::set('services.google.workspace.admin_email', 'admin@afges.org');
    Config::set('services.google.workspace.delegate_group', 'test-mon@afges.org');

    // Create only one delegate with this email
    $user = User::factory()->create();
    $delegate = Delegate::factory()->create([
        'user_id' => $user->id,
        'email' => 'unique@afges.org',
        'email_verified_at' => now(),
        'started_at' => now()->subDays(10),
        'ended_at' => null,
    ]);

    // Mock service SHOULD be called since this is the last delegate with this email
    $mockWorkspaceService = mock(GoogleWorkspaceServiceInterface::class);
    $mockWorkspaceService->shouldReceive('removeFromGroup')
        ->once()
        ->with('unique@afges.org')
        ->andReturn(true);

    $syncService = new DelegateGoogleWorkspaceSyncService($mockWorkspaceService);

    $observer = new DelegateObserver($syncService);

    // Delete delegate - should remove from group
    $observer->deleted($delegate);
});

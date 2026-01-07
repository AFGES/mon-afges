<?php

use App\Models\Delegate;
use App\Models\User;
use App\Observers\DelegateObserver;
use App\Services\GoogleWorkspaceService;
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

    $mockService = mock(GoogleWorkspaceService::class)->makePartial();
    $mockService->shouldReceive('addToGroup')
        ->once()
        ->with('delegate@afges.org')
        ->andReturn(true);

    $observer = new DelegateObserver($mockService);

    $user = User::factory()->create();
    $delegate = Delegate::factory()->make([
        'user_id' => $user->id,
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

    $user = User::factory()->create();
    $delegate = Delegate::factory()->create([
        'user_id' => $user->id,
        'email' => 'delegate@afges.org',
        'started_at' => now()->subDays(10),
        'ended_at' => null,
    ]);

    // Now update the delegate to mark it as ended
    $delegate->ended_at = now();
    $delegate->syncChanges(); // Mark as changed

    $mockService = mock(GoogleWorkspaceService::class)->makePartial();
    $mockService->shouldReceive('removeFromGroup')
        ->once()
        ->with('delegate@afges.org')
        ->andReturn(true);

    $observer = new DelegateObserver($mockService);

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

    $mockService = mock(GoogleWorkspaceService::class)->makePartial();
    $mockService->shouldReceive('removeFromGroup')
        ->once()
        ->with('delegate@afges.org')
        ->andReturn(true);

    $observer = new DelegateObserver($mockService);

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

    $mockService = mock(GoogleWorkspaceService::class)->makePartial();
    $mockService->shouldReceive('addToGroup')
        ->once()
        ->with('delegate@afges.org')
        ->andReturn(true);

    $observer = new DelegateObserver($mockService);

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
            return $message === 'Cannot sync delegate to Google Workspace: email missing';
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
            return $message === 'Cannot sync delegate to Google Workspace: email not verified'
                && $context['email'] === 'unverified@afges.org';
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
    $user1 = User::factory()->create();
    $existingDelegate = Delegate::factory()->create([
        'user_id' => $user1->id,
        'email' => 'shared@afges.org',
        'email_verified_at' => now(),
        'started_at' => now()->subDays(10),
        'ended_at' => null,
    ]);

    // Mock service should NOT be called since email is already in use
    $mockService = mock(GoogleWorkspaceService::class)->makePartial();
    $mockService->shouldNotReceive('addToGroup');

    Log::shouldReceive('info')
        ->once()
        ->withArgs(function ($message, $context) {
            return $message === 'Skipping Google Workspace add: email already in use by other active delegates'
                && $context['email'] === 'shared@afges.org';
        });

    $observer = new DelegateObserver($mockService);

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
    $delegate1 = Delegate::factory()->create([
        'user_id' => $user1->id,
        'email' => 'shared@afges.org',
        'email_verified_at' => now(),
        'started_at' => now()->subDays(10),
        'ended_at' => null,
    ]);

    $user2 = User::factory()->create();
    $delegate2 = Delegate::factory()->create([
        'user_id' => $user2->id,
        'email' => 'shared@afges.org',
        'email_verified_at' => now(),
        'started_at' => now()->subDays(5),
        'ended_at' => null,
    ]);

    // Mock service should NOT be called since email is still in use by delegate2
    $mockService = mock(GoogleWorkspaceService::class)->makePartial();
    $mockService->shouldNotReceive('removeFromGroup');

    Log::shouldReceive('info')
        ->once()
        ->withArgs(function ($message, $context) use ($delegate1) {
            return $message === 'Skipping Google Workspace removal: email still in use by other active delegates'
                && $context['delegate_id'] === $delegate1->id
                && $context['email'] === 'shared@afges.org';
        });

    $observer = new DelegateObserver($mockService);

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
    $mockService = mock(GoogleWorkspaceService::class)->makePartial();
    $mockService->shouldReceive('removeFromGroup')
        ->once()
        ->with('unique@afges.org')
        ->andReturn(true);

    $observer = new DelegateObserver($mockService);

    // Delete delegate - should remove from group
    $observer->deleted($delegate);
});

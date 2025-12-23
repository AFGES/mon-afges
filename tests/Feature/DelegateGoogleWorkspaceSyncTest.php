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
        ->andReturn(true);

    $observer = new DelegateObserver($mockService);

    $user = User::factory()->create(['email' => 'delegate@afges.org']);
    $delegate = Delegate::factory()->make([
        'user_id' => $user->id,
        'started_at' => now(),
        'ended_at' => null,
    ]);

    $observer->created($delegate);
});

test('delegate observer removes user from google workspace group when ended', function () {
    Config::set('services.google.workspace.service_account_json', '{"type":"service_account"}');
    Config::set('services.google.workspace.admin_email', 'admin@afges.org');
    Config::set('services.google.workspace.delegate_group', 'test-mon@afges.org');

    $user = User::factory()->create(['email' => 'delegate@afges.org']);
    $delegate = Delegate::factory()->create([
        'user_id' => $user->id,
        'started_at' => now()->subDays(10),
        'ended_at' => null,
    ]);

    // Now update the delegate to mark it as ended
    $delegate->ended_at = now();
    $delegate->syncChanges(); // Mark as changed

    $mockService = mock(GoogleWorkspaceService::class)->makePartial();
    $mockService->shouldReceive('removeFromGroup')
        ->once()
        ->with($user->email)
        ->andReturn(true);

    $observer = new DelegateObserver($mockService);

    $observer->updated($delegate);
});

test('delegate observer removes user from google workspace group when deleted', function () {
    Config::set('services.google.workspace.service_account_json', '{"type":"service_account"}');
    Config::set('services.google.workspace.admin_email', 'admin@afges.org');
    Config::set('services.google.workspace.delegate_group', 'test-mon@afges.org');

    $user = User::factory()->create(['email' => 'delegate@afges.org']);
    $delegate = Delegate::factory()->make([
        'user_id' => $user->id,
        'started_at' => now()->subDays(10),
        'ended_at' => null,
    ]);

    $mockService = mock(GoogleWorkspaceService::class)->makePartial();
    $mockService->shouldReceive('removeFromGroup')
        ->once()
        ->andReturn(true);

    $observer = new DelegateObserver($mockService);

    $observer->deleted($delegate);
});

test('delegate observer re-adds user to google workspace group when restored', function () {
    Config::set('services.google.workspace.service_account_json', '{"type":"service_account"}');
    Config::set('services.google.workspace.admin_email', 'admin@afges.org');
    Config::set('services.google.workspace.delegate_group', 'test-mon@afges.org');

    $user = User::factory()->create(['email' => 'delegate@afges.org']);
    $delegate = Delegate::factory()->make([
        'user_id' => $user->id,
        'started_at' => now()->subDays(10),
        'ended_at' => null,
    ]);

    $mockService = mock(GoogleWorkspaceService::class)->makePartial();
    $mockService->shouldReceive('addToGroup')
        ->once()
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

test('delegate sync handles user without email gracefully', function () {
    Config::set('services.google.workspace.service_account_json', '{"type":"service_account"}');
    Config::set('services.google.workspace.admin_email', 'admin@afges.org');
    Config::set('services.google.workspace.delegate_group', 'test-mon@afges.org');

    Log::shouldReceive('warning')
        ->once()
        ->withArgs(function ($message, $context) {
            return $message === 'Cannot sync delegate to Google Workspace: user or email missing';
        });

    $observer = new DelegateObserver;
    $delegate = Delegate::factory()->make([
        'user_id' => null,
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

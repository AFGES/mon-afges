<?php

declare(strict_types=1);

use App\Livewire\Profile\ManageDelegates;
use App\Models\Delegate;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('user can terminate their approved delegate mandate', function () {
    $user = User::factory()->create();
    $delegate = Delegate::factory()->approved()->create([
        'user_id' => $user->id,
        'ended_at' => null,
    ]);

    actingAs($user);

    expect($delegate->ended_at)->toBeNull();

    Livewire::test(ManageDelegates::class)
        ->call('terminateDelegate', $delegate->id)
        ->assertNotified();

    $delegate->refresh();
    expect($delegate->ended_at)->not->toBeNull();
});

test('user cannot terminate another users delegate mandate', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $delegate = Delegate::factory()->approved()->create([
        'user_id' => $otherUser->id,
        'ended_at' => null,
    ]);

    actingAs($user);

    Livewire::test(ManageDelegates::class)
        ->call('terminateDelegate', $delegate->id)
        ->assertNotified();

    // Verify it wasn't terminated
    $delegate->refresh();
    expect($delegate->ended_at)->toBeNull();
});

test('user cannot terminate pending delegate mandate', function () {
    $user = User::factory()->create();
    $delegate = Delegate::factory()->create([
        'user_id' => $user->id,
        'status' => \App\Enums\ApprovalStatus::Pending,
        'approved_at' => null,
        'approved_by' => null,
        'ended_at' => null,
    ]);

    actingAs($user);

    Livewire::test(ManageDelegates::class)
        ->call('terminateDelegate', $delegate->id)
        ->assertNotified();

    $delegate->refresh();
    expect($delegate->ended_at)->toBeNull();
});

test('user cannot terminate already terminated delegate mandate', function () {
    $user = User::factory()->create();
    $delegate = Delegate::factory()->approved()->create([
        'user_id' => $user->id,
        'ended_at' => now()->subDays(5),
    ]);

    actingAs($user);

    Livewire::test(ManageDelegates::class)
        ->call('terminateDelegate', $delegate->id)
        ->assertNotified();

    // Verify ended_at didn't change
    $originalEndedAt = $delegate->ended_at->toDateTimeString();
    $delegate->refresh();
    expect($delegate->ended_at->toDateTimeString())->toBe($originalEndedAt);
});

test('delegate list displays ended date for terminated mandates', function () {
    $user = User::factory()->create();
    $activeDelegate = Delegate::factory()->approved()->create([
        'user_id' => $user->id,
        'ended_at' => null,
    ]);
    $endedDelegate = Delegate::factory()->approved()->create([
        'user_id' => $user->id,
        'ended_at' => now()->subDays(10),
    ]);

    actingAs($user);

    $component = Livewire::test(ManageDelegates::class);

    $component->assertSee($activeDelegate->organization->short_name);
    $component->assertSee($endedDelegate->organization->short_name);
    $component->assertSee('Terminé le');
});

test('delegate terminate method sets ended_at to current time', function () {
    $delegate = Delegate::factory()->approved()->create(['ended_at' => null]);

    expect($delegate->ended_at)->toBeNull();

    $result = $delegate->terminate();

    expect($result)->toBeTrue();
    expect($delegate->fresh()->ended_at)->not->toBeNull();
});

test('delegate terminate method returns false if already terminated', function () {
    $delegate = Delegate::factory()->approved()->create([
        'ended_at' => now()->subDays(5),
    ]);

    $originalEndedAt = $delegate->ended_at;
    $result = $delegate->terminate();

    expect($result)->toBeFalse();
    expect($delegate->fresh()->ended_at->toDateString())->toBe($originalEndedAt->toDateString());
});

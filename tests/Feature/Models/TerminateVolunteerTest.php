<?php

declare(strict_types=1);

use App\Livewire\Profile\ManageVolunteers;
use App\Models\User;
use App\Models\Volunteer;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

test('user can terminate their approved volunteer mandate', function () {
    $user = User::factory()->create();
    $volunteer = Volunteer::factory()->approved()->create(['user_id' => $user->id]);

    actingAs($user);

    expect($volunteer->ended_at)->toBeNull();

    livewire(ManageVolunteers::class)
        ->call('terminateVolunteer', $volunteer->id)
        ->assertNotified();

    $volunteer->refresh();
    expect($volunteer->ended_at)->not->toBeNull();
});

test('user cannot terminate another users volunteer mandate', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $volunteer = Volunteer::factory()->approved()->create(['user_id' => $otherUser->id]);

    actingAs($user);

    livewire(ManageVolunteers::class)
        ->call('terminateVolunteer', $volunteer->id)
        ->assertNotified();

    // Verify it wasn't terminated
    $volunteer->refresh();
    expect($volunteer->ended_at)->toBeNull();
});

test('user cannot terminate pending volunteer mandate', function () {
    $user = User::factory()->create();
    $volunteer = Volunteer::withoutGlobalScopes()->create(
        Volunteer::factory()->make(['user_id' => $user->id])->toArray()
    );

    actingAs($user);

    livewire(ManageVolunteers::class)
        ->call('terminateVolunteer', $volunteer->id)
        ->assertNotified();

    $volunteer->refresh();
    expect($volunteer->ended_at)->toBeNull();
});

test('user cannot terminate already terminated volunteer mandate', function () {
    $user = User::factory()->create();
    $volunteer = Volunteer::factory()->approved()->create([
        'user_id' => $user->id,
        'ended_at' => now()->subDays(5),
    ]);

    actingAs($user);

    livewire(ManageVolunteers::class)
        ->call('terminateVolunteer', $volunteer->id)
        ->assertNotified();

    // Verify ended_at didn't change
    $originalEndedAt = $volunteer->ended_at->toDateTimeString();
    $volunteer->refresh();
    expect($volunteer->ended_at->toDateTimeString())->toBe($originalEndedAt);
});

test('volunteer list displays ended date for terminated mandates', function () {
    $user = User::factory()->create();
    $activeVolunteer = Volunteer::factory()->approved()->create(['user_id' => $user->id]);
    $endedVolunteer = Volunteer::factory()->approved()->create([
        'user_id' => $user->id,
        'ended_at' => now()->subDays(10),
    ]);

    actingAs($user);

    $component = livewire(ManageVolunteers::class);

    $component->assertSee($activeVolunteer->organization->short_name);
    $component->assertSee($endedVolunteer->organization->short_name);
    $component->assertSee('Terminé le');
});

test('volunteer terminate method sets ended_at to current time', function () {
    $volunteer = Volunteer::factory()->approved()->create(['ended_at' => null]);

    expect($volunteer->ended_at)->toBeNull();

    $result = $volunteer->end();

    expect($result)->toBeTrue();
    expect($volunteer->fresh()->ended_at)->not->toBeNull();
});

test('volunteer terminate method returns false if already terminated', function () {
    $volunteer = Volunteer::factory()->approved()->create([
        'ended_at' => now()->subDays(5),
    ]);

    $originalEndedAt = $volunteer->ended_at;
    $result = $volunteer->end();

    expect($result)->toBeFalse();
    expect($volunteer->fresh()->ended_at->toDateTimeString())->toBe($originalEndedAt->toDateTimeString());
});

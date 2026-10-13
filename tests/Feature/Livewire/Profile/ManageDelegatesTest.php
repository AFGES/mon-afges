<?php

declare(strict_types=1);

use App\Livewire\Profile\ManageDelegates;
use App\Models\CollegeOrganization;
use App\Models\Delegate;
use App\Models\Organization;
use App\Models\Seat;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\freezeTime;
use function Pest\Laravel\travelTo;
use function Pest\Livewire\livewire;

test('creates a pending delegate application for the chosen seat', function () {
    freezeTime();
    $user = User::factory()->create();
    $collegeOrganization = CollegeOrganization::factory()->create(['delegates_per_seat' => 2]);
    $seat = Seat::factory()->create(['college_organization_id' => $collegeOrganization->id]);

    actingAs($user);

    livewire(ManageDelegates::class)
        ->fillForm([
            'applications' => [
                [
                    'organization_id' => $collegeOrganization->organization_id,
                    'seat_id' => $seat->id,
                    'email' => $user->email,
                    'started_at' => now()->subDays(7)->toDateString(),
                ],
            ],
        ])
        ->call('submitDelegates')
        ->assertHasNoFormErrors()
        ->assertNotified(__('profile.manage_delegates.notification_title'));

    $delegate = Delegate::withoutGlobalScopes()->where('user_id', $user->id)->sole();

    expect($delegate)
        ->organization_id->toBe($collegeOrganization->organization_id)
        ->seat_id->toBe($seat->id)
        ->email->toBe($user->email)
        ->started_at->toDateString()->toBe(now()->subDays(7)->toDateString())
        ->isPending()->toBeTrue();
});

test('rejects a delegate application without seat', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    actingAs($user);

    livewire(ManageDelegates::class)
        ->fillForm([
            'applications' => [
                [
                    'organization_id' => $organization->id,
                    'seat_id' => null,
                    'email' => $user->email,
                    'started_at' => now()->toDateString(),
                ],
            ],
        ])
        ->call('submitDelegates')
        ->assertHasFormErrors(['applications.0.seat_id' => 'required']);

    expect(Delegate::withoutGlobalScopes()->count())->toBe(0);
});

test('does nothing when no delegate application is submitted', function () {
    $user = User::factory()->create();

    actingAs($user);

    livewire(ManageDelegates::class)
        ->fillForm(['applications' => []])
        ->call('submitDelegates')
        ->assertNotNotified();

    expect(Delegate::withoutGlobalScopes()->count())->toBe(0);
});

test('terminates the approved delegate mandate of the user', function () {
    travelTo('2026-03-15 12:00:00');
    $user = User::factory()->create();
    $delegate = Delegate::factory()->approved()->create([
        'user_id' => $user->id,
        'ended_at' => null,
    ]);

    actingAs($user);

    livewire(ManageDelegates::class)
        ->call('terminateDelegate', $delegate->id)
        ->assertNotified(__('profile.manage_delegates.terminate.success_title'));

    expect($delegate->refresh()->ended_at->toDateString())->toBe('2026-03-15');
});

test('refuses to terminate the delegate mandate of another user', function () {
    $user = User::factory()->create();
    $delegate = Delegate::factory()->approved()->create(['ended_at' => null]);

    actingAs($user);

    livewire(ManageDelegates::class)
        ->call('terminateDelegate', $delegate->id)
        ->assertNotified(__('profile.manage_delegates.terminate.not_authorized_title'));

    expect($delegate->refresh()->ended_at)->toBeNull();
});

test('refuses to terminate a pending delegate mandate', function () {
    $user = User::factory()->create();
    $delegate = Delegate::factory()->pending()->create([
        'user_id' => $user->id,
        'ended_at' => null,
    ]);

    actingAs($user);

    livewire(ManageDelegates::class)
        ->call('terminateDelegate', $delegate->id)
        ->assertNotified(__('profile.manage_delegates.terminate.not_authorized_title'));

    expect($delegate->refresh()->ended_at)->toBeNull();
});

test('refuses to terminate an already terminated delegate mandate', function () {
    travelTo('2026-03-15 12:00:00');
    $user = User::factory()->create();
    $delegate = Delegate::factory()->approved()->create([
        'user_id' => $user->id,
        'ended_at' => '2026-03-10',
    ]);

    actingAs($user);

    livewire(ManageDelegates::class)
        ->call('terminateDelegate', $delegate->id)
        ->assertNotified(__('profile.manage_delegates.terminate.not_authorized_title'));

    expect($delegate->refresh()->ended_at->toDateString())->toBe('2026-03-10');
});

test('displays the end date of terminated mandates', function () {
    travelTo('2026-03-15 12:00:00');
    $user = User::factory()->create();
    Delegate::factory()->approved()->create([
        'user_id' => $user->id,
        'ended_at' => null,
    ]);
    Delegate::factory()->approved()->create([
        'user_id' => $user->id,
        'ended_at' => '2026-03-05',
    ]);

    actingAs($user);

    livewire(ManageDelegates::class)
        ->assertSee(__('profile.manage_delegates.ended_on', ['date' => '05/03/2026']));
});

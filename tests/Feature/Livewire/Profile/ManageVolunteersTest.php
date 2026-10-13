<?php

declare(strict_types=1);

use App\Livewire\Profile\ManageVolunteers;
use App\Models\Organization;
use App\Models\User;
use App\Models\Volunteer;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\freezeTime;
use function Pest\Laravel\travelTo;
use function Pest\Livewire\livewire;

test('creates a pending volunteer application for each chosen organization', function () {
    freezeTime();
    $user = User::factory()->create();
    $firstOrganization = Organization::factory()->create();
    $secondOrganization = Organization::factory()->create();

    actingAs($user);

    livewire(ManageVolunteers::class)
        ->fillForm([
            'organizations' => [
                [
                    'organization_id' => $firstOrganization->id,
                    'started_at' => now()->subDays(7)->toDateString(),
                ],
                [
                    'organization_id' => $secondOrganization->id,
                    'started_at' => now()->subDays(14)->toDateString(),
                ],
            ],
        ])
        ->call('submitVolunteers')
        ->assertHasNoFormErrors()
        ->assertNotified(__('volunteer.notifications.pending_approval.title'));

    $firstVolunteer = Volunteer::withoutGlobalScopes()->where('organization_id', $firstOrganization->id)->sole();
    $secondVolunteer = Volunteer::withoutGlobalScopes()->where('organization_id', $secondOrganization->id)->sole();

    expect($firstVolunteer)
        ->user_id->toBe($user->id)
        ->started_at->toDateString()->toBe(now()->subDays(7)->toDateString())
        ->isPending()->toBeTrue()
        ->and($secondVolunteer)
        ->user_id->toBe($user->id)
        ->started_at->toDateString()->toBe(now()->subDays(14)->toDateString())
        ->isPending()->toBeTrue();
});

test('rejects a volunteer application without organization', function () {
    $user = User::factory()->create();
    Organization::factory()->create();

    actingAs($user);

    livewire(ManageVolunteers::class)
        ->fillForm([
            'organizations' => [
                [
                    'organization_id' => null,
                    'started_at' => now()->toDateString(),
                ],
            ],
        ])
        ->call('submitVolunteers')
        ->assertHasFormErrors(['organizations.0.organization_id' => 'required']);

    expect(Volunteer::withoutGlobalScopes()->count())->toBe(0);
});

test('does nothing when no volunteer application is submitted', function () {
    $user = User::factory()->create();
    Organization::factory()->create();

    actingAs($user);

    livewire(ManageVolunteers::class)
        ->fillForm(['organizations' => []])
        ->call('submitVolunteers')
        ->assertNotNotified();

    expect(Volunteer::withoutGlobalScopes()->count())->toBe(0);
});

test('terminates the approved volunteer mandate of the user', function () {
    travelTo('2026-03-15 12:00:00');
    $user = User::factory()->create();
    $volunteer = Volunteer::factory()->approved()->create(['user_id' => $user->id]);

    actingAs($user);

    livewire(ManageVolunteers::class)
        ->call('terminateVolunteer', $volunteer->id)
        ->assertNotified(__('profile.manage_volunteers.terminate.success_title'));

    expect($volunteer->refresh()->ended_at->toDateTimeString())->toBe('2026-03-15 12:00:00');
});

test('refuses to terminate the volunteer mandate of another user', function () {
    $user = User::factory()->create();
    $volunteer = Volunteer::factory()->approved()->create();

    actingAs($user);

    livewire(ManageVolunteers::class)
        ->call('terminateVolunteer', $volunteer->id)
        ->assertNotified(__('profile.manage_volunteers.terminate.not_authorized_title'));

    expect($volunteer->refresh()->ended_at)->toBeNull();
});

test('refuses to terminate a pending volunteer mandate', function () {
    $user = User::factory()->create();
    $volunteer = Volunteer::factory()->pending()->create(['user_id' => $user->id]);

    actingAs($user);

    livewire(ManageVolunteers::class)
        ->call('terminateVolunteer', $volunteer->id)
        ->assertNotified(__('profile.manage_volunteers.terminate.not_authorized_title'));

    expect($volunteer->refresh()->ended_at)->toBeNull();
});

test('refuses to terminate an already terminated volunteer mandate', function () {
    travelTo('2026-03-15 12:00:00');
    $user = User::factory()->create();
    $volunteer = Volunteer::factory()->approved()->create([
        'user_id' => $user->id,
        'ended_at' => '2026-03-10 09:30:00',
    ]);

    actingAs($user);

    livewire(ManageVolunteers::class)
        ->call('terminateVolunteer', $volunteer->id)
        ->assertNotified(__('profile.manage_volunteers.terminate.not_authorized_title'));

    expect($volunteer->refresh()->ended_at->toDateTimeString())->toBe('2026-03-10 09:30:00');
});

test('displays the end date of terminated mandates', function () {
    travelTo('2026-03-15 12:00:00');
    $user = User::factory()->create();
    Volunteer::factory()->approved()->create(['user_id' => $user->id]);
    Volunteer::factory()->approved()->create([
        'user_id' => $user->id,
        'ended_at' => '2026-03-05',
    ]);

    actingAs($user);

    livewire(ManageVolunteers::class)
        ->assertSee(__('profile.manage_volunteers.ended_on', ['date' => '05/03/2026']));
});

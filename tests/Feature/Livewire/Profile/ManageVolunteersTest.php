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
    Volunteer::factory()->approved()->for($user)->for($secondOrganization)->create();

    actingAs($user);

    livewire(ManageVolunteers::class)
        ->fillForm([
            'organizations' => [
                [
                    'organization_id' => $firstOrganization->id,
                    'position_title' => 'Trésorier',
                    'started_at' => now()->subDays(7)->toDateString(),
                ],
                [
                    'organization_id' => $secondOrganization->id,
                    'position_title' => 'Trésorier',
                    'started_at' => now()->subDays(14)->toDateString(),
                ],
            ],
        ])
        ->call('submitVolunteers')
        ->assertHasNoFormErrors()
        ->assertNotified(__('volunteer.notifications.pending_approval.title'));

    $firstVolunteer = Volunteer::withoutGlobalScopes()->where('organization_id', $firstOrganization->id)->sole();
    $secondVolunteer = Volunteer::withoutGlobalScopes()->where('organization_id', $secondOrganization->id)->pending()->sole();

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

test('does not list soft-deleted volunteers', function () {
    $user = User::factory()->create();
    Volunteer::factory()->pending()->create(['user_id' => $user->id])->delete();

    actingAs($user);

    expect(livewire(ManageVolunteers::class)->instance()->volunteers)->toBeEmpty();
});

test('rejects a request for a new organization while another one is pending', function () {
    $user = User::factory()->create();
    $pendingOrganization = Organization::factory()->create(['short_name' => 'AFGES']);
    $otherOrganization = Organization::factory()->create();
    Volunteer::factory()->pending()->for($user)->for($pendingOrganization)->create();

    actingAs($user);

    livewire(ManageVolunteers::class)
        ->fillForm([
            'organizations' => [
                ['organization_id' => $otherOrganization->id, 'position_title' => 'Trésorier', 'started_at' => now()->toDateString()],
            ],
        ])
        ->call('submitVolunteers')
        ->assertHasFormErrors([
            'organizations.0.organization_id' => [__('profile.manage_volunteers.request_locked.message', ['organization' => 'AFGES'])],
        ]);

    expect(Volunteer::withoutGlobalScopes()->where('organization_id', $otherOrganization->id)->exists())->toBeFalse();
});

test('allows a request for a new organization after a rejected one', function () {
    $user = User::factory()->create();
    $otherOrganization = Organization::factory()->create();
    Volunteer::factory()->rejected()->for($user)->create();

    actingAs($user);

    livewire(ManageVolunteers::class)
        ->fillForm([
            'organizations' => [
                ['organization_id' => $otherOrganization->id, 'position_title' => 'Trésorier', 'started_at' => now()->toDateString()],
            ],
        ])
        ->call('submitVolunteers')
        ->assertHasNoFormErrors();

    expect(Volunteer::withoutGlobalScopes()->where('organization_id', $otherOrganization->id)->sole()->isPending())->toBeTrue();
});

test('allows a request for a linked organization while a new one is pending', function () {
    $user = User::factory()->create();
    $linkedOrganization = Organization::factory()->create();
    Volunteer::factory()->approved()->for($user)->for($linkedOrganization)->create();
    Volunteer::factory()->pending()->for($user)->create();

    actingAs($user);

    livewire(ManageVolunteers::class)
        ->fillForm([
            'organizations' => [
                ['organization_id' => $linkedOrganization->id, 'position_title' => 'Trésorier', 'started_at' => now()->toDateString()],
            ],
        ])
        ->call('submitVolunteers')
        ->assertHasNoFormErrors();

    expect(Volunteer::withoutGlobalScopes()->where('organization_id', $linkedOrganization->id)->pending()->exists())->toBeTrue();
});

test('rejects two new organizations in a single submission', function () {
    $user = User::factory()->create();
    $firstOrganization = Organization::factory()->create();
    $secondOrganization = Organization::factory()->create();

    actingAs($user);

    livewire(ManageVolunteers::class)
        ->fillForm([
            'organizations' => [
                ['organization_id' => $firstOrganization->id, 'position_title' => 'Trésorier', 'started_at' => now()->toDateString()],
                ['organization_id' => $secondOrganization->id, 'position_title' => 'Trésorier', 'started_at' => now()->toDateString()],
            ],
        ])
        ->call('submitVolunteers')
        ->assertHasFormErrors([
            'organizations' => [__('profile.manage_volunteers.request_locked.single_new_organization')],
        ]);

    expect(Volunteer::withoutGlobalScopes()->count())->toBe(0);
});

test('displays the pending request banner', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create(['short_name' => 'AFGES']);
    Volunteer::factory()->pending()->for($user)->for($organization)->create();

    actingAs($user);

    livewire(ManageVolunteers::class)
        ->assertSee(__('profile.manage_volunteers.request_locked.message', ['organization' => 'AFGES']));
});

test('defines the missing position title of the user', function () {
    $user = User::factory()->create();
    $volunteer = Volunteer::factory()->approved()->for($user)->create(['position_title' => null]);

    actingAs($user);

    livewire(ManageVolunteers::class)
        ->assertSee(__('profile.manage_volunteers.position_title_required.banner'))
        ->fillForm([$volunteer->id => 'Trésorier'], 'positionTitleForm')
        ->call('definePositionTitles')
        ->assertHasNoFormErrors(form: 'positionTitleForm')
        ->assertNotified(__('profile.manage_volunteers.position_title_required.success_title'))
        ->assertDontSee(__('profile.manage_volunteers.position_title_required.banner'));

    expect($volunteer->refresh()->position_title)->toBe('Trésorier');
});

test('requires a value for the missing position title', function () {
    $user = User::factory()->create();
    $volunteer = Volunteer::factory()->approved()->for($user)->create(['position_title' => null]);

    actingAs($user);

    livewire(ManageVolunteers::class)
        ->fillForm([$volunteer->id => ''], 'positionTitleForm')
        ->call('definePositionTitles')
        ->assertHasFormErrors([$volunteer->id => [__('profile.form.position_title.required')]], 'positionTitleForm');

    expect($volunteer->refresh()->position_title)->toBeNull();
});

test('does not define the position title of another user', function () {
    $user = User::factory()->create();
    $otherVolunteer = Volunteer::factory()->approved()->create(['position_title' => null]);

    actingAs($user);

    livewire(ManageVolunteers::class)
        ->set("positionTitles.{$otherVolunteer->id}", 'Président')
        ->call('definePositionTitles');

    expect($otherVolunteer->refresh()->position_title)->toBeNull();
});

test('shows the pending request lock right after submitting a new organization', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create(['short_name' => 'AFGES']);

    actingAs($user);

    livewire(ManageVolunteers::class)
        ->assertDontSee(__('profile.manage_volunteers.request_locked.message', ['organization' => 'AFGES']))
        ->fillForm([
            'organizations' => [
                ['organization_id' => $organization->id, 'position_title' => 'Trésorier', 'started_at' => now()->toDateString()],
            ],
        ])
        ->call('submitVolunteers')
        ->assertHasNoFormErrors()
        ->assertSee(__('profile.manage_volunteers.request_locked.message', ['organization' => 'AFGES']));
});

test('requires a position title when requesting an organization', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    actingAs($user);

    livewire(ManageVolunteers::class)
        ->fillForm([
            'organizations' => [
                ['organization_id' => $organization->id, 'position_title' => null, 'started_at' => now()->toDateString()],
            ],
        ])
        ->call('submitVolunteers')
        ->assertHasFormErrors(['organizations.0.position_title' => [__('profile.form.position_title.required')]]);

    expect(Volunteer::withoutGlobalScopes()->count())->toBe(0);
});

test('ignores a pending request for a deleted organization', function () {
    $user = User::factory()->create();
    $deletedOrganization = Organization::factory()->create(['short_name' => 'AFGES']);
    Volunteer::factory()->pending()->for($user)->for($deletedOrganization)->create();
    $deletedOrganization->delete();
    $otherOrganization = Organization::factory()->create();

    actingAs($user);

    livewire(ManageVolunteers::class)
        ->assertDontSee(__('profile.manage_volunteers.request_locked.message', ['organization' => 'AFGES']))
        ->fillForm([
            'organizations' => [
                ['organization_id' => $otherOrganization->id, 'position_title' => 'Trésorier', 'started_at' => now()->toDateString()],
            ],
        ])
        ->call('submitVolunteers')
        ->assertHasNoFormErrors();
});

<?php

declare(strict_types=1);

use App\Filament\Pages\RequestDelegateSeat;
use App\Livewire\Profile\ManageDelegates;
use App\Models\CollegeOrganization;
use App\Models\Delegate;
use App\Models\Seat;
use App\Models\User;
use App\Models\Volunteer;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;
use function Pest\Livewire\livewire;

test('displays the fiche date of existing delegates', function () {
    travelTo('2026-03-15 12:00:00');
    $user = User::factory()->create();
    Delegate::factory()->pending()->create(['user_id' => $user->id]);

    actingAs($user);

    livewire(ManageDelegates::class)
        ->assertSee(__('delegate_form.fields.submitted_at', ['date' => '15/03/2026']));
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

test('shows the request link to a user with an approved volunteer mandate in an organization with seats', function () {
    $user = User::factory()->create();
    $collegeOrganization = CollegeOrganization::factory()->create();
    Seat::factory()->create(['college_organization_id' => $collegeOrganization->id]);
    Volunteer::factory()->approved()->create([
        'user_id' => $user->id,
        'organization_id' => $collegeOrganization->organization_id,
        'ended_at' => null,
    ]);

    actingAs($user);

    livewire(ManageDelegates::class)
        ->assertSee(__('profile.manage_delegates.request_action'))
        ->assertSeeHtml(RequestDelegateSeat::getUrl());
});

test('hides the request link from a user whose volunteer mandate is pending', function () {
    $user = User::factory()->create();
    $collegeOrganization = CollegeOrganization::factory()->create();
    Seat::factory()->create(['college_organization_id' => $collegeOrganization->id]);
    Volunteer::factory()->pending()->create([
        'user_id' => $user->id,
        'organization_id' => $collegeOrganization->organization_id,
        'ended_at' => null,
    ]);

    actingAs($user);

    livewire(ManageDelegates::class)
        ->assertDontSee(__('profile.manage_delegates.request_action'));
});

test('renders the terminate action with a quoted delegate id', function () {
    $user = User::factory()->create();
    $delegate = Delegate::factory()->approved()->create([
        'user_id' => $user->id,
        'ended_at' => null,
    ]);

    actingAs($user);

    livewire(ManageDelegates::class)
        ->assertSeeHtml("terminateDelegate('{$delegate->id}')");
});

test('does not list soft-deleted delegates', function () {
    $user = User::factory()->create();
    $delegate = Delegate::factory()->pending()->create(['user_id' => $user->id]);
    $delegate->delete();

    actingAs($user);

    expect(livewire(ManageDelegates::class)->instance()->delegates)->toBeEmpty();
});

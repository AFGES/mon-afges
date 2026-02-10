<?php

declare(strict_types=1);

use App\Filament\Resources\Volunteers\Widgets\PendingVolunteerStats;
use App\Models\User;
use App\Models\Volunteer;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'Update:Volunteer', 'guard_name' => 'web']);
});

test('pending volunteer stats widget displays correct count', function () {
    $admin = User::factory()->create();

    // Create some pending volunteers
    Volunteer::withoutGlobalScopes()->create(
        Volunteer::factory()->make()->toArray()
    );
    Volunteer::withoutGlobalScopes()->create(
        Volunteer::factory()->make()->toArray()
    );
    Volunteer::withoutGlobalScopes()->create(
        Volunteer::factory()->make()->toArray()
    );

    // Create an approved volunteer (should not be counted)
    Volunteer::factory()->approved()->create();

    actingAs($admin);

    Livewire::test(PendingVolunteerStats::class)
        ->assertSee('3')
        ->assertSee(__('volunteer.stats.pending_approvals'));
});

test('pending volunteer stats widget shows zero when no pending volunteers', function () {
    $admin = User::factory()->create();

    // Create only approved volunteers
    Volunteer::factory()->approved()->create();
    Volunteer::factory()->approved()->create();

    actingAs($admin);

    Livewire::test(PendingVolunteerStats::class)
        ->assertSee('0')
        ->assertSee(__('volunteer.stats.pending_approvals'));
});

test('widget counts only pending volunteers not approved ones', function () {
    $admin = User::factory()->create();

    // Create 2 pending volunteers
    Volunteer::withoutGlobalScopes()->create(
        Volunteer::factory()->make()->toArray()
    );
    Volunteer::withoutGlobalScopes()->create(
        Volunteer::factory()->make()->toArray()
    );

    // Create 3 approved volunteers (should not be counted)
    Volunteer::factory()->approved()->create();
    Volunteer::factory()->approved()->create();
    Volunteer::factory()->approved()->create();

    // Create 1 rejected volunteer (should not be counted)
    Volunteer::factory()->rejected()->create();

    actingAs($admin);

    $pendingCount = Volunteer::withoutGlobalScopes()
        ->pending()
        ->count();

    expect($pendingCount)->toBe(2);

    Livewire::test(PendingVolunteerStats::class)
        ->assertSee('2');
});

test('widget is only visible to users with View:PendingVolunteerStats permission', function () {
    Permission::firstOrCreate(['name' => 'View:PendingVolunteerStats', 'guard_name' => 'web']);

    $userWithPermission = User::factory()->create();
    $userWithPermission->givePermissionTo('View:PendingVolunteerStats');

    $userWithoutPermission = User::factory()->create();

    actingAs($userWithPermission);
    expect(PendingVolunteerStats::canView())
        ->toBeTrue('Widget should be visible to users with View:PendingVolunteerStats permission');

    actingAs($userWithoutPermission);
    expect(PendingVolunteerStats::canView())
        ->toBeFalse('Widget should not be visible to users without View:PendingVolunteerStats permission');
});

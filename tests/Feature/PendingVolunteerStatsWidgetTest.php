<?php

declare(strict_types=1);

use App\Filament\Resources\Volunteers\Widgets\PendingVolunteerStats;
use App\Models\User;
use App\Models\Volunteer;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

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
        ->where('status', \App\Enums\ApprovalStatus::Pending)
        ->count();

    expect($pendingCount)->toBe(2);

    Livewire::test(PendingVolunteerStats::class)
        ->assertSee('2');
});

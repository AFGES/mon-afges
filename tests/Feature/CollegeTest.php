<?php

use App\Models\College;
use App\Models\CollegeOrganization;
use App\Models\Delegate;
use App\Models\Organization;
use App\Models\Seat;
use App\Models\User;

test('college has delegates through college organizations', function () {
    $college = College::factory()->create();
    $organization = Organization::factory()->create();

    $collegeOrg = CollegeOrganization::factory()
        ->for($college)
        ->for($organization)
        ->withSeats(2)
        ->create();

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $seat = $collegeOrg->seatRecords->first();

    Delegate::factory()->create([
        'seat_id' => $seat->id,
        'user_id' => $user1->id,
        'organization_id' => $organization->id,
    ]);

    Delegate::factory()->create([
        'seat_id' => $seat->id,
        'user_id' => $user2->id,
        'organization_id' => $organization->id,
    ]);

    // Get delegates through seats via college organizations
    $delegateCount = Delegate::withoutGlobalScopes()
        ->whereIn('seat_id', $collegeOrg->seatRecords->pluck('id'))
        ->count();

    expect($delegateCount)->toBe(2);
});

test('college can be votable', function () {
    $college = College::factory()->create(['can_vote' => true]);

    expect($college->can_vote)->toBeTrue();
});

test('college can be non-votable', function () {
    $college = College::factory()->create(['can_vote' => false]);

    expect($college->can_vote)->toBeFalse();
});

test('college has max seats per organization constraint', function () {
    $college = College::factory()->create(['max_seats_per_organization' => 10]);

    expect($college->max_seats_per_organization)->toBe(10);
});

test('college has max delegates per seat constraint', function () {
    $college = College::factory()->create(['max_delegates_per_seat' => 5]);

    expect($college->max_delegates_per_seat)->toBe(5);
});

test('college has many seats through college organizations', function () {
    $college = College::factory()->create();
    $organization1 = Organization::factory()->create();
    $organization2 = Organization::factory()->create();

    $collegeOrg1 = CollegeOrganization::factory()
        ->for($college)
        ->for($organization1)
        ->withSeats(3)
        ->create();

    $collegeOrg2 = CollegeOrganization::factory()
        ->for($college)
        ->for($organization2)
        ->withSeats(2)
        ->create();

    $seatCount = Seat::whereIn('college_organization_id', [$collegeOrg1->id, $collegeOrg2->id])->count();

    expect($seatCount)->toBe(5);
});

test('college has organizations relationship', function () {
    $college = College::factory()->create();
    $org1 = Organization::factory()->create();
    $org2 = Organization::factory()->create();

    $college->organizations()->attach($org1->id, ['delegates_per_seat' => 2]);
    $college->organizations()->attach($org2->id, ['delegates_per_seat' => 1]);

    expect($college->organizations)->toHaveCount(2);
});

test('college organizations relationship includes pivot data', function () {
    $college = College::factory()->create();
    $organization = Organization::factory()->create();

    $college->organizations()->attach($organization->id, ['delegates_per_seat' => 3]);

    $pivot = $college->organizations()->first()->pivot;

    expect($pivot->delegates_per_seat)->toBe(3);
});

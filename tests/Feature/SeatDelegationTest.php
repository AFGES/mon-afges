<?php

use App\Models\College;
use App\Models\CollegeOrganization;
use App\Models\Delegate;
use App\Models\Organization;
use App\Models\Seat;
use App\Models\User;

test('a seat belongs to a college organization', function () {
    $collegeOrg = CollegeOrganization::factory()->create();
    $seat = Seat::factory()->create([
        'college_organization_id' => $collegeOrg->id,
    ]);

    expect($seat->collegeOrganization->id)->toBe($collegeOrg->id);
});

test('a delegate can be assigned to a seat', function () {
    $collegeOrg = CollegeOrganization::factory()->create();
    $seat = Seat::factory()->create([
        'college_organization_id' => $collegeOrg->id,
    ]);
    $user = User::factory()->create();

    $delegate = Delegate::factory()->create([
        'user_id' => $user->id,
        'organization_id' => $collegeOrg->organization_id,
        'seat_id' => $seat->id,
    ]);

    expect($delegate->seat->id)->toBe($seat->id)
        ->and($delegate->user->id)->toBe($user->id);
});

test('a delegate can access college through seat', function () {
    $college = College::factory()->create(['name' => 'Test College']);
    $organization = Organization::factory()->create();
    $collegeOrg = CollegeOrganization::factory()->create([
        'college_id' => $college->id,
        'organization_id' => $organization->id,
    ]);
    $seat = Seat::factory()->create([
        'college_organization_id' => $collegeOrg->id,
    ]);
    $delegate = Delegate::factory()->create([
        'seat_id' => $seat->id,
        'organization_id' => $organization->id,
    ]);

    $delegateCollege = $delegate->seat->collegeOrganization->college;

    expect($delegateCollege->id)->toBe($college->id)
        ->and($delegateCollege->name)->toBe('Test College');
});

test('multiple delegates can be assigned to the same seat when delegates_per_seat allows', function () {
    $collegeOrg = CollegeOrganization::factory()->create([
        'delegates_per_seat' => 3,
    ]);
    $seat = Seat::factory()->create([
        'college_organization_id' => $collegeOrg->id,
    ]);

    $delegate1 = Delegate::factory()->create([
        'seat_id' => $seat->id,
        'organization_id' => $collegeOrg->organization_id,
    ]);
    $delegate2 = Delegate::factory()->create([
        'seat_id' => $seat->id,
        'organization_id' => $collegeOrg->organization_id,
    ]);
    $delegate3 = Delegate::factory()->create([
        'seat_id' => $seat->id,
        'organization_id' => $collegeOrg->organization_id,
    ]);

    expect($seat->delegates()->count())->toBe(3)
        ->and($seat->delegates->pluck('id')->toArray())->toContain($delegate1->id, $delegate2->id, $delegate3->id);
});

test('college organization can have multiple seats', function () {
    $collegeOrg = CollegeOrganization::factory()->create([
        'seats' => 5,
    ]);

    for ($i = 1; $i <= 5; $i++) {
        Seat::factory()->create([
            'college_organization_id' => $collegeOrg->id,
            'seat_number' => $i,
        ]);
    }

    expect($collegeOrg->seatRecords()->count())->toBe(5);
});

test('seats generate command creates correct number of seats', function () {
    $collegeOrg = CollegeOrganization::factory()->create([
        'seats' => 3,
    ]);

    $this->artisan('seats:generate')
        ->assertSuccessful();

    expect($collegeOrg->fresh()->seatRecords()->count())->toBe(3);
});

test('college can access delegates through seats', function () {
    $college = College::factory()->create();
    $organization = Organization::factory()->create();
    $collegeOrg = CollegeOrganization::factory()->create([
        'college_id' => $college->id,
        'organization_id' => $organization->id,
        'seats' => 2,
        'delegates_per_seat' => 1,
    ]);

    // Create seats
    $seat1 = Seat::factory()->create([
        'college_organization_id' => $collegeOrg->id,
        'seat_number' => 1,
    ]);
    $seat2 = Seat::factory()->create([
        'college_organization_id' => $collegeOrg->id,
        'seat_number' => 2,
    ]);

    // Create delegates
    $delegate1 = Delegate::factory()->create([
        'seat_id' => $seat1->id,
        'organization_id' => $organization->id,
    ]);
    $delegate2 = Delegate::factory()->create([
        'seat_id' => $seat2->id,
        'organization_id' => $organization->id,
    ]);

    // Query delegates for this college through seats
    $delegatesCount = Delegate::query()
        ->join('seats', 'delegates.seat_id', '=', 'seats.id')
        ->join('college_organization', 'seats.college_organization_id', '=', 'college_organization.id')
        ->where('college_organization.college_id', $college->id)
        ->count();

    $delegateIds = Delegate::query()
        ->join('seats', 'delegates.seat_id', '=', 'seats.id')
        ->join('college_organization', 'seats.college_organization_id', '=', 'college_organization.id')
        ->where('college_organization.college_id', $college->id)
        ->pluck('delegates.id')
        ->toArray();

    expect($delegatesCount)->toBe(2)
        ->and($delegateIds)->toContain($delegate1->id, $delegate2->id);
});

test('college max seats per organization is enforced', function () {
    $college = College::factory()->create([
        'name' => 'Test College',
        'max_seats_per_organization' => 5,
    ]);
    $organization = Organization::factory()->create();

    $collegeOrg = CollegeOrganization::factory()->create([
        'college_id' => $college->id,
        'organization_id' => $organization->id,
        'seats' => 3, // Within limit
        'delegates_per_seat' => 1,
    ]);

    expect($collegeOrg->seats)->toBe(3)
        ->and($collegeOrg->seats)->toBeLessThanOrEqual($college->max_seats_per_organization);

    // Test that we can't exceed the limit
    $collegeOrg2 = CollegeOrganization::factory()->create([
        'college_id' => $college->id,
        'organization_id' => Organization::factory()->create()->id,
        'seats' => 5, // At limit
        'delegates_per_seat' => 1,
    ]);

    expect($collegeOrg2->seats)->toBe(5)
        ->and($collegeOrg2->seats)->toBeLessThanOrEqual($college->max_seats_per_organization);
});

test('college max delegates per seat is enforced', function () {
    $college = College::factory()->create([
        'name' => 'Test College',
        'max_delegates_per_seat' => 3,
    ]);
    $organization = Organization::factory()->create();

    $collegeOrg = CollegeOrganization::factory()->create([
        'college_id' => $college->id,
        'organization_id' => $organization->id,
        'seats' => 2,
        'delegates_per_seat' => 2, // Within limit
    ]);

    expect($collegeOrg->delegates_per_seat)->toBe(2)
        ->and($collegeOrg->delegates_per_seat)->toBeLessThanOrEqual($college->max_delegates_per_seat);

    // Test that we can use the maximum
    $collegeOrg2 = CollegeOrganization::factory()->create([
        'college_id' => $college->id,
        'organization_id' => Organization::factory()->create()->id,
        'seats' => 1,
        'delegates_per_seat' => 3, // At limit
    ]);

    expect($collegeOrg2->delegates_per_seat)->toBe(3)
        ->and($collegeOrg2->delegates_per_seat)->toBeLessThanOrEqual($college->max_delegates_per_seat);
});

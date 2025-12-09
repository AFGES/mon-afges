<?php

use App\Models\College;
use App\Models\CollegeOrganization;
use App\Models\Delegate;
use App\Models\Organization;
use App\Models\Seat;

test('seats can be created for college organization', function () {
    $collegeOrg = CollegeOrganization::factory()->withSeats(3)->create();

    $seats = $collegeOrg->seatRecords;
    $seat1 = $seats->where('seat_number', 1)->first();
    $seat2 = $seats->where('seat_number', 2)->first();
    $seat3 = $seats->where('seat_number', 3)->first();

    expect($collegeOrg->seatRecords)->toHaveCount(3)
        ->and($seat1->collegeOrganization->id)->toBe($collegeOrg->id)
        ->and($seat2->seat_number)->toBe(2)
        ->and($seat3->seat_number)->toBe(3);
});

test('seat can have multiple delegates when allowed', function () {
    $collegeOrg = CollegeOrganization::factory()
        ->withSeats(1)
        ->create([
            'delegates_per_seat' => 3,
        ]);

    $seat = $collegeOrg->seatRecords->first();

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

    expect($seat->delegates)->toHaveCount(3)
        ->and($seat->delegates->pluck('id')->toArray())->toContain($delegate1->id, $delegate2->id, $delegate3->id);
});

test('seat belongs to correct college through college organization', function () {
    $college = College::factory()->create(['name' => 'Test College']);
    $organization = Organization::factory()->create();

    $collegeOrg = CollegeOrganization::factory()->create([
        'college_id' => $college->id,
        'organization_id' => $organization->id,
    ]);

    $seat = Seat::factory()->create([
        'college_organization_id' => $collegeOrg->id,
        'seat_number' => 1,
    ]);

    expect($seat->collegeOrganization->college->id)->toBe($college->id)
        ->and($seat->collegeOrganization->college->name)->toBe('Test College')
        ->and($seat->collegeOrganization->organization->id)->toBe($organization->id);
});

test('seats are numbered sequentially per college organization', function () {
    $collegeOrg = CollegeOrganization::factory()->withSeats(10)->create();

    $seatNumbers = $collegeOrg->seatRecords->pluck('seat_number')->sort()->values()->toArray();

    expect($seatNumbers)->toBe([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
});

test('deleting college organization deletes associated seats', function () {
    $collegeOrg = CollegeOrganization::factory()->withSeats(3)->create();

    expect(Seat::count())->toBe(3);

    $collegeOrg->delete();

    // Seats should be deleted (if cascade is set up, or handle manually in your code)
    // This depends on your foreign key constraints
    expect(Seat::where('college_organization_id', $collegeOrg->id)->count())->toBe(0);
});

test('college max constraints are validated', function () {
    $college = College::factory()->create([
        'max_seats_per_organization' => 5,
        'max_delegates_per_seat' => 3,
    ]);

    $organization = Organization::factory()->create();

    // Valid within constraints
    $collegeOrg = CollegeOrganization::factory()
        ->for($college)
        ->for($organization)
        ->withSeats(5)
        ->create([
            'delegates_per_seat' => 3,
        ]);

    expect($collegeOrg->seats)->toBeLessThanOrEqual($college->max_seats_per_organization)
        ->and($collegeOrg->delegates_per_seat)->toBeLessThanOrEqual($college->max_delegates_per_seat);
});

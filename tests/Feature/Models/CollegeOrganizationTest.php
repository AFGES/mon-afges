<?php

declare(strict_types=1);

use App\Models\CollegeOrganization;
use App\Models\Seat;

test('counts its seat records as seats', function () {
    $collegeOrganization = CollegeOrganization::factory()->withSeats(3)->create();

    expect($collegeOrganization->seats)->toBe(3);
});

test('multiplies seats by delegates per seat to get the total delegate positions', function () {
    $collegeOrganization = CollegeOrganization::factory()
        ->withSeats(4)
        ->create(['delegates_per_seat' => 3]);

    expect($collegeOrganization->total_delegate_positions)->toBe(12);
});

test('has no delegate positions without seats', function () {
    $collegeOrganization = CollegeOrganization::factory()->create(['delegates_per_seat' => 5]);

    expect($collegeOrganization->total_delegate_positions)->toBe(0);
});

test('deletes its seats when it is deleted', function () {
    $collegeOrganization = CollegeOrganization::factory()->withSeats(3)->create();

    $collegeOrganization->delete();

    expect(Seat::withTrashed()->where('college_organization_id', $collegeOrganization->id)->count())->toBe(0);
});

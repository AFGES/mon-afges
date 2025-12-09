<?php

use App\Models\College;
use App\Models\CollegeOrganization;
use App\Models\Delegate;
use App\Models\Organization;
use App\Models\Seat;
use App\Models\User;

test('seat is empty returns true when no delegates', function () {
    $collegeOrg = CollegeOrganization::factory()->withSeats(1)->create();
    $seat = $collegeOrg->seatRecords->first();

    expect($seat->isEmpty())->toBeTrue();
});

test('seat is empty returns false when has delegates', function () {
    $collegeOrg = CollegeOrganization::factory()->withSeats(1)->create();
    $seat = $collegeOrg->seatRecords->first();

    $user = User::factory()->create();
    Delegate::factory()->create([
        'seat_id' => $seat->id,
        'user_id' => $user->id,
        'organization_id' => $collegeOrg->organization_id,
    ]);

    expect($seat->isEmpty())->toBeFalse();
});

test('seat can access college through college organization', function () {
    $college = College::factory()->create(['name' => 'Test College']);
    $organization = Organization::factory()->create();

    $collegeOrg = CollegeOrganization::factory()
        ->for($college)
        ->for($organization)
        ->withSeats(1)
        ->create();

    $seat = $collegeOrg->seatRecords->first();

    expect($seat->college()->first()->name)->toBe('Test College');
});

test('seat can access organization through college organization', function () {
    $college = College::factory()->create();
    $organization = Organization::factory()->create(['short_name' => 'TEST']);

    $collegeOrg = CollegeOrganization::factory()
        ->for($college)
        ->for($organization)
        ->withSeats(1)
        ->create();

    $seat = $collegeOrg->seatRecords->first();

    expect($seat->organization()->first()->short_name)->toBe('TEST');
});

test('seat has seat number', function () {
    $collegeOrg = CollegeOrganization::factory()->withSeats(5)->create();
    $seats = $collegeOrg->seatRecords;

    expect($seats->pluck('seat_number')->sort()->values()->toArray())->toBe([1, 2, 3, 4, 5]);
});

test('seat can be soft deleted', function () {
    $collegeOrg = CollegeOrganization::factory()->withSeats(3)->create();
    $seat = $collegeOrg->seatRecords->first();

    expect(Seat::count())->toBe(3);

    $seat->delete();

    expect(Seat::count())->toBe(2)
        ->and(Seat::withTrashed()->count())->toBe(3);
});

test('seat can be restored after soft delete', function () {
    $collegeOrg = CollegeOrganization::factory()->withSeats(2)->create();
    $seat = $collegeOrg->seatRecords->first();

    $seat->delete();
    expect(Seat::count())->toBe(1);

    $seat->restore();
    expect(Seat::count())->toBe(2);
});

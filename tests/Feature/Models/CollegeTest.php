<?php

declare(strict_types=1);

use App\Models\College;
use App\Models\CollegeOrganization;
use App\Models\Delegate;
use App\Models\Organization;

test('lists the seats of all its organizations', function () {
    $college = College::factory()->create();
    $firstCollegeOrganization = CollegeOrganization::factory()->for($college)->withSeats(3)->create();
    $secondCollegeOrganization = CollegeOrganization::factory()->for($college)->withSeats(2)->create();
    CollegeOrganization::factory()->withSeats(4)->create();

    $seatIds = $college->seats()->pluck('seats.id');

    expect($seatIds->all())->toEqualCanonicalizing(
        $firstCollegeOrganization->seatRecords->merge($secondCollegeOrganization->seatRecords)->pluck('id')->all()
    );
});

test('lists only the delegates seated in that college', function () {
    $college = College::factory()->create();
    $collegeOrganization = CollegeOrganization::factory()->for($college)->withSeats(2)->create();
    $otherCollegeOrganization = CollegeOrganization::factory()->withSeats(1)->create();
    $delegatesInCollege = $collegeOrganization->seatRecords->map(fn ($seat) => Delegate::factory()->approved()->create([
        'seat_id' => $seat->id,
        'organization_id' => $collegeOrganization->organization_id,
    ]));
    Delegate::factory()->approved()->create([
        'seat_id' => $otherCollegeOrganization->seatRecords->first()->id,
        'organization_id' => $otherCollegeOrganization->organization_id,
    ]);

    $delegateIds = $college->delegates()->pluck('delegates.id');

    expect($delegateIds->all())->toEqualCanonicalizing($delegatesInCollege->pluck('id')->all());
});

test('exposes the delegates per seat of each organization on the pivot', function () {
    $college = College::factory()->create();
    $firstOrganization = Organization::factory()->create();
    $secondOrganization = Organization::factory()->create();
    $college->organizations()->attach($firstOrganization->id, ['delegates_per_seat' => 2]);
    $college->organizations()->attach($secondOrganization->id, ['delegates_per_seat' => 3]);

    $delegatesPerSeat = $college->organizations->mapWithKeys(
        fn (Organization $organization) => [$organization->id => $organization->pivot->delegates_per_seat]
    );

    expect($delegatesPerSeat->all())->toEqual([
        $firstOrganization->id => 2,
        $secondOrganization->id => 3,
    ]);
});

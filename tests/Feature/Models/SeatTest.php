<?php

declare(strict_types=1);

use App\Models\College;
use App\Models\CollegeOrganization;
use App\Models\Delegate;
use App\Models\Organization;
use App\Models\User;

test('is empty when no delegate sits on it', function () {
    $seat = CollegeOrganization::factory()->withSeats(1)->create()->seatRecords->first();

    expect($seat->isEmpty())->toBeTrue();
});

test('is not empty when an approved delegate sits on it', function () {
    $collegeOrganization = CollegeOrganization::factory()->withSeats(1)->create();
    $seat = $collegeOrganization->seatRecords->first();
    Delegate::factory()->approved()->create([
        'seat_id' => $seat->id,
        'organization_id' => $collegeOrganization->organization_id,
    ]);

    expect($seat->isEmpty())->toBeFalse();
});

test('lists only its approved delegates', function () {
    $collegeOrganization = CollegeOrganization::factory()->withSeats(1)->create(['delegates_per_seat' => 3]);
    $seat = $collegeOrganization->seatRecords->first();
    $approvedDelegates = Delegate::factory()->approved()->count(2)->create([
        'seat_id' => $seat->id,
        'organization_id' => $collegeOrganization->organization_id,
    ]);
    Delegate::factory()->pending()->create([
        'seat_id' => $seat->id,
        'organization_id' => $collegeOrganization->organization_id,
    ]);

    expect($seat->delegates->pluck('id')->all())
        ->toEqualCanonicalizing($approvedDelegates->pluck('id')->all());
});

test('resolves its college through its college organization', function () {
    $college = College::factory()->create();
    $seat = CollegeOrganization::factory()->for($college)->withSeats(1)->create()->seatRecords->first();

    expect($seat->college->is($college))->toBeTrue();
});

test('resolves its organization through its college organization', function () {
    $organization = Organization::factory()->create();
    $seat = CollegeOrganization::factory()->for($organization)->withSeats(1)->create()->seatRecords->first();

    expect($seat->organization->is($organization))->toBeTrue();
});

test('labels itself with the college name and the delegate names', function () {
    $college = College::factory()->create(['name' => 'Général']);
    $collegeOrganization = CollegeOrganization::factory()->for($college)->withSeats(1)->create(['delegates_per_seat' => 2]);
    $seat = $collegeOrganization->seatRecords->first();
    Delegate::factory()->approved()->for(User::factory()->state(['first_name' => 'Alice', 'last_name' => 'Martin']), 'user')->create([
        'seat_id' => $seat->id,
        'organization_id' => $collegeOrganization->organization_id,
    ]);

    expect($seat->label())->toBe('Général: Alice Martin');
});

test('labels itself with every delegate name separated by commas', function () {
    $college = College::factory()->create(['name' => 'Général']);
    $collegeOrganization = CollegeOrganization::factory()->for($college)->withSeats(1)->create(['delegates_per_seat' => 2]);
    $seat = $collegeOrganization->seatRecords->first();
    foreach ([['Alice', 'Martin'], ['Bob', 'Durand']] as [$firstName, $lastName]) {
        Delegate::factory()->approved()->for(User::factory()->state(['first_name' => $firstName, 'last_name' => $lastName]), 'user')->create([
            'seat_id' => $seat->id,
            'organization_id' => $collegeOrganization->organization_id,
        ]);
    }

    expect($seat->label())->toBeIn(['Général: Alice Martin, Bob Durand', 'Général: Bob Durand, Alice Martin']);
});

test('labels itself as vide when no delegate sits on it', function () {
    $college = College::factory()->create(['name' => 'Fédéral']);
    $seat = CollegeOrganization::factory()->for($college)->withSeats(1)->create()->seatRecords->first();

    expect($seat->label())->toBe('Fédéral: Vide');
});

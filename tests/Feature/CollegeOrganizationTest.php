<?php

use App\Models\College;
use App\Models\CollegeOrganization;
use App\Models\Organization;
use App\Models\Seat;

test('college organization pivot has correct relationships', function () {
    $college = College::factory()->create();
    $organization = Organization::factory()->create();

    $collegeOrg = CollegeOrganization::factory()->create([
        'college_id' => $college->id,
        'organization_id' => $organization->id,
        'seats' => 3,
        'delegates_per_seat' => 2,
    ]);

    expect($collegeOrg->college->id)->toBe($college->id)
        ->and($collegeOrg->organization->id)->toBe($organization->id)
        ->and($collegeOrg->seats)->toBe(3)
        ->and($collegeOrg->delegates_per_seat)->toBe(2);
});

test('college organization calculates total delegate positions', function () {
    $collegeOrg = CollegeOrganization::factory()->create([
        'seats' => 4,
        'delegates_per_seat' => 3,
    ]);

    expect($collegeOrg->total_delegate_positions)->toBe(12);
});

test('college organization can have seat records', function () {
    $collegeOrg = CollegeOrganization::factory()->create([
        'seats' => 5,
    ]);

    for ($i = 1; $i <= 5; $i++) {
        Seat::factory()->create([
            'college_organization_id' => $collegeOrg->id,
            'seat_number' => $i,
        ]);
    }

    expect($collegeOrg->seatRecords)->toHaveCount(5)
        ->and($collegeOrg->seatRecords->pluck('seat_number')->toArray())->toBe([1, 2, 3, 4, 5]);
});

test('college can have multiple organizations', function () {
    $college = College::factory()->create();
    $org1 = Organization::factory()->create();
    $org2 = Organization::factory()->create();
    $org3 = Organization::factory()->create();

    CollegeOrganization::factory()->create([
        'college_id' => $college->id,
        'organization_id' => $org1->id,
        'seats' => 2,
    ]);

    CollegeOrganization::factory()->create([
        'college_id' => $college->id,
        'organization_id' => $org2->id,
        'seats' => 3,
    ]);

    CollegeOrganization::factory()->create([
        'college_id' => $college->id,
        'organization_id' => $org3->id,
        'seats' => 1,
    ]);

    expect($college->organizations)->toHaveCount(3);
});

test('organization can have multiple colleges', function () {
    $organization = Organization::factory()->create();
    $college1 = College::factory()->create();
    $college2 = College::factory()->create();
    $college3 = College::factory()->create();

    CollegeOrganization::factory()->create([
        'college_id' => $college1->id,
        'organization_id' => $organization->id,
    ]);

    CollegeOrganization::factory()->create([
        'college_id' => $college2->id,
        'organization_id' => $organization->id,
    ]);

    CollegeOrganization::factory()->create([
        'college_id' => $college3->id,
        'organization_id' => $organization->id,
    ]);

    expect($organization->colleges)->toHaveCount(3);
});

test('college organization with zero seats has zero positions', function () {
    $collegeOrg = CollegeOrganization::factory()->create([
        'seats' => 0,
        'delegates_per_seat' => 5,
    ]);

    expect($collegeOrg->total_delegate_positions)->toBe(0);
});

test('college organization pivot data persists correctly', function () {
    $college = College::factory()->create();
    $organization = Organization::factory()->create();

    $organization->colleges()->attach($college->id, [
        'seats' => 7,
        'delegates_per_seat' => 4,
    ]);

    $pivotData = $organization->colleges()->first()->pivot;

    expect($pivotData->seats)->toBe(7)
        ->and($pivotData->delegates_per_seat)->toBe(4)
        ->and($pivotData->college_id)->toBe($college->id)
        ->and($pivotData->organization_id)->toBe($organization->id);
});

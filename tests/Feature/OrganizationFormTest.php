<?php

use App\Models\College;
use App\Models\Organization;

test('organization can be created with college associations', function () {
    $college1 = College::factory()->create(['name' => 'College A']);
    $college2 = College::factory()->create(['name' => 'College B']);

    $organization = Organization::factory()->create([
        'short_name' => 'Test Org',
        'legal_name' => 'Test Organization Legal Name',
    ]);

    $organization->syncCollegesWithSeats([
        $college1->id => ['delegates_per_seat' => 2, 'seats' => 3],
        $college2->id => ['delegates_per_seat' => 1, 'seats' => 5],
    ]);

    expect($organization->colleges)->toHaveCount(2);

    $collegeOrg1 = \App\Models\CollegeOrganization::where('college_id', $college1->id)
        ->where('organization_id', $organization->id)
        ->first();

    expect($collegeOrg1->seats)->toBe(3)
        ->and($collegeOrg1->delegates_per_seat)->toBe(2);
});

test('organization respects college max seats per organization', function () {
    $college = College::factory()->create([
        'name' => 'Limited College',
        'max_seats_per_organization' => 5,
    ]);

    $organization = Organization::factory()->create();

    // Within limit
    $organization->syncCollegesWithSeats([
        $college->id => ['delegates_per_seat' => 1, 'seats' => 3],
    ]);

    $collegeOrg = \App\Models\CollegeOrganization::where('college_id', $college->id)
        ->where('organization_id', $organization->id)
        ->first();

    expect($collegeOrg->seats)->toBe(3)
        ->and($collegeOrg->seats)->toBeLessThanOrEqual($college->max_seats_per_organization);
});

test('organization respects college max delegates per seat', function () {
    $college = College::factory()->create([
        'name' => 'Limited Delegates College',
        'max_delegates_per_seat' => 3,
    ]);

    $organization = Organization::factory()->create();

    // Within limit
    $organization->syncCollegesWithSeats([
        $college->id => ['delegates_per_seat' => 2, 'seats' => 2],
    ]);

    $collegeOrg = \App\Models\CollegeOrganization::where('college_id', $college->id)
        ->where('organization_id', $organization->id)
        ->first();

    expect($collegeOrg->delegates_per_seat)->toBe(2)
        ->and($collegeOrg->delegates_per_seat)->toBeLessThanOrEqual($college->max_delegates_per_seat);
});

test('organization can update college associations', function () {
    $college = College::factory()->create();
    $organization = Organization::factory()->create();

    // Initial association
    $organization->syncCollegesWithSeats([
        $college->id => ['delegates_per_seat' => 1, 'seats' => 2],
    ]);

    $collegeOrg = \App\Models\CollegeOrganization::where('college_id', $college->id)
        ->where('organization_id', $organization->id)
        ->first();

    expect($collegeOrg->seats)->toBe(2);

    // Update association - increase seats
    $organization->syncCollegesWithSeats([
        $college->id => ['delegates_per_seat' => 2, 'seats' => 4],
    ]);

    $collegeOrg = \App\Models\CollegeOrganization::where('college_id', $college->id)
        ->where('organization_id', $organization->id)
        ->first();

    expect($collegeOrg->seats)->toBe(4)
        ->and($collegeOrg->delegates_per_seat)->toBe(2);
});

test('organization can remove college associations', function () {
    $college1 = College::factory()->create();
    $college2 = College::factory()->create();
    $organization = Organization::factory()->create();

    $organization->colleges()->attach([
        $college1->id => ['seats' => 2, 'delegates_per_seat' => 1],
        $college2->id => ['seats' => 3, 'delegates_per_seat' => 1],
    ]);

    expect($organization->colleges)->toHaveCount(2);

    // Remove one association
    $organization->colleges()->detach($college1->id);

    expect($organization->fresh()->colleges)->toHaveCount(1)
        ->and($organization->fresh()->colleges->first()->id)->toBe($college2->id);
});

test('multiple organizations can be associated with same college', function () {
    $college = College::factory()->create();
    $org1 = Organization::factory()->create(['short_name' => 'Org 1']);
    $org2 = Organization::factory()->create(['short_name' => 'Org 2']);
    $org3 = Organization::factory()->create(['short_name' => 'Org 3']);

    $org1->colleges()->attach($college->id, ['seats' => 2, 'delegates_per_seat' => 1]);
    $org2->colleges()->attach($college->id, ['seats' => 3, 'delegates_per_seat' => 2]);
    $org3->colleges()->attach($college->id, ['seats' => 1, 'delegates_per_seat' => 1]);

    expect($college->organizations)->toHaveCount(3)
        ->and($college->organizations->pluck('short_name')->toArray())->toContain('Org 1', 'Org 2', 'Org 3');
});

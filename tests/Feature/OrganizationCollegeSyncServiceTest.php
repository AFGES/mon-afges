<?php

use App\Models\College;
use App\Models\CollegeOrganization;
use App\Models\Delegate;
use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationCollegeSyncService;

test('organization college sync service syncs colleges with seats', function () {
    $college1 = College::factory()->create();
    $college2 = College::factory()->create();
    $organization = Organization::factory()->create();

    $service = new OrganizationCollegeSyncService;

    $service->sync($organization, [
        $college1->id => ['delegates_per_seat' => 2, 'seats' => 3],
        $college2->id => ['delegates_per_seat' => 1, 'seats' => 5],
    ]);

    expect($organization->colleges)->toHaveCount(2);

    $collegeOrg1 = CollegeOrganization::where('college_id', $college1->id)
        ->where('organization_id', $organization->id)
        ->first();

    $collegeOrg2 = CollegeOrganization::where('college_id', $college2->id)
        ->where('organization_id', $organization->id)
        ->first();

    expect($collegeOrg1->seats)->toBe(3)
        ->and($collegeOrg1->delegates_per_seat)->toBe(2)
        ->and($collegeOrg2->seats)->toBe(5)
        ->and($collegeOrg2->delegates_per_seat)->toBe(1);
});

test('organization college sync service adds seats when increasing', function () {
    $college = College::factory()->create();
    $organization = Organization::factory()->create();

    $service = new OrganizationCollegeSyncService;

    // Initial sync with 2 seats
    $service->sync($organization, [
        $college->id => ['delegates_per_seat' => 1, 'seats' => 2],
    ]);

    $collegeOrg = CollegeOrganization::where('college_id', $college->id)
        ->where('organization_id', $organization->id)
        ->first();

    expect($collegeOrg->seats)->toBe(2);

    // Increase to 5 seats
    $service->sync($organization, [
        $college->id => ['delegates_per_seat' => 1, 'seats' => 5],
    ]);

    expect($collegeOrg->fresh()->seats)->toBe(5);
});

test('organization college sync service removes empty seats when decreasing', function () {
    $college = College::factory()->create();
    $organization = Organization::factory()->create();

    $service = new OrganizationCollegeSyncService;

    // Initial sync with 5 seats
    $service->sync($organization, [
        $college->id => ['delegates_per_seat' => 1, 'seats' => 5],
    ]);

    $collegeOrg = CollegeOrganization::where('college_id', $college->id)
        ->where('organization_id', $organization->id)
        ->first();

    expect($collegeOrg->seats)->toBe(5);

    // Decrease to 3 seats
    $service->sync($organization, [
        $college->id => ['delegates_per_seat' => 1, 'seats' => 3],
    ]);

    expect($collegeOrg->fresh()->seats)->toBe(3);
});

test('organization college sync service throws exception when trying to remove occupied seats', function () {
    $college = College::factory()->create();
    $organization = Organization::factory()->create();

    $service = new OrganizationCollegeSyncService;

    // Initial sync with 3 seats
    $service->sync($organization, [
        $college->id => ['delegates_per_seat' => 1, 'seats' => 3],
    ]);

    $collegeOrg = CollegeOrganization::where('college_id', $college->id)
        ->where('organization_id', $organization->id)
        ->first();

    // Assign delegates to all 3 seats
    $user = User::factory()->create();
    foreach ($collegeOrg->seatRecords as $seat) {
        Delegate::factory()->approved()->create([
            'seat_id' => $seat->id,
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ]);
    }

    // Try to decrease to 1 seat (should fail)
    expect(fn () => $service->sync($organization, [
        $college->id => ['delegates_per_seat' => 1, 'seats' => 1],
    ]))->toThrow(Exception::class, 'Cannot remove 2 seats. Only 0 empty seats available.');
});

test('organization college sync service removes colleges not in data', function () {
    $college1 = College::factory()->create();
    $college2 = College::factory()->create();
    $college3 = College::factory()->create();
    $organization = Organization::factory()->create();

    $service = new OrganizationCollegeSyncService;

    // Add 3 colleges
    $service->sync($organization, [
        $college1->id => ['delegates_per_seat' => 1, 'seats' => 2],
        $college2->id => ['delegates_per_seat' => 1, 'seats' => 2],
        $college3->id => ['delegates_per_seat' => 1, 'seats' => 2],
    ]);

    expect($organization->fresh()->colleges)->toHaveCount(3);

    // Remove college3 by not including it
    $service->sync($organization, [
        $college1->id => ['delegates_per_seat' => 1, 'seats' => 2],
        $college2->id => ['delegates_per_seat' => 1, 'seats' => 2],
    ]);

    expect($organization->fresh()->colleges)->toHaveCount(2);
});

test('organization college sync service updates delegates per seat', function () {
    $college = College::factory()->create();
    $organization = Organization::factory()->create();

    $service = new OrganizationCollegeSyncService;

    // Initial sync
    $service->sync($organization, [
        $college->id => ['delegates_per_seat' => 1, 'seats' => 2],
    ]);

    $collegeOrg = CollegeOrganization::where('college_id', $college->id)
        ->where('organization_id', $organization->id)
        ->first();

    expect($collegeOrg->delegates_per_seat)->toBe(1);

    // Update delegates per seat
    $service->sync($organization, [
        $college->id => ['delegates_per_seat' => 3, 'seats' => 2],
    ]);

    expect($collegeOrg->fresh()->delegates_per_seat)->toBe(3);
});

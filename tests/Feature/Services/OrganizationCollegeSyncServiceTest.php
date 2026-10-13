<?php

declare(strict_types=1);

use App\Models\College;
use App\Models\CollegeOrganization;
use App\Models\Delegate;
use App\Models\Organization;
use App\Models\Seat;
use App\Services\OrganizationCollegeSyncService;

function collegeOrganizationFor(Organization $organization, College $college): CollegeOrganization
{
    return CollegeOrganization::where('college_id', $college->id)
        ->where('organization_id', $organization->id)
        ->sole();
}

/**
 * @return array<int, int>
 */
function seatNumbersOf(CollegeOrganization $collegeOrganization): array
{
    return $collegeOrganization->seatRecords()->orderBy('seat_number')->pluck('seat_number')->all();
}

it('attaches colleges with their delegates per seat and creates numbered seats', function () {
    $college1 = College::factory()->create();
    $college2 = College::factory()->create();
    $organization = Organization::factory()->create();

    (new OrganizationCollegeSyncService)->sync($organization, [
        $college1->id => ['delegates_per_seat' => 2, 'seats' => 3],
        $college2->id => ['delegates_per_seat' => 1, 'seats' => 5],
    ]);

    $collegeOrganization1 = collegeOrganizationFor($organization, $college1);
    $collegeOrganization2 = collegeOrganizationFor($organization, $college2);
    expect($organization->colleges)->toHaveCount(2)
        ->and($collegeOrganization1->delegates_per_seat)->toBe(2)
        ->and(seatNumbersOf($collegeOrganization1))->toBe([1, 2, 3])
        ->and($collegeOrganization2->delegates_per_seat)->toBe(1)
        ->and(seatNumbersOf($collegeOrganization2))->toBe([1, 2, 3, 4, 5]);
    $this->assertDatabaseCount('seats', 8);
});

it('does not create seats when the seat count is omitted', function () {
    $college = College::factory()->create();
    $organization = Organization::factory()->create();

    (new OrganizationCollegeSyncService)->sync($organization, [
        $college->id => ['delegates_per_seat' => 2],
    ]);

    expect($organization->colleges)->toHaveCount(1);
    $this->assertDatabaseCount('seats', 0);
});

it('appends seats after the highest existing number when increasing', function () {
    $college = College::factory()->create();
    $organization = Organization::factory()->create();
    $service = new OrganizationCollegeSyncService;
    $service->sync($organization, [$college->id => ['delegates_per_seat' => 1, 'seats' => 2]]);

    $service->sync($organization, [$college->id => ['delegates_per_seat' => 1, 'seats' => 5]]);

    expect(seatNumbersOf(collegeOrganizationFor($organization, $college)))->toBe([1, 2, 3, 4, 5]);
    $this->assertDatabaseCount('seats', 5);
});

it('removes the empty seats with the highest numbers when decreasing', function () {
    $college = College::factory()->create();
    $organization = Organization::factory()->create();
    $service = new OrganizationCollegeSyncService;
    $service->sync($organization, [$college->id => ['delegates_per_seat' => 1, 'seats' => 5]]);
    $collegeOrganization = collegeOrganizationFor($organization, $college);
    $occupiedSeat = $collegeOrganization->seatRecords()->where('seat_number', 5)->sole();
    $removedSeats = $collegeOrganization->seatRecords()->whereIn('seat_number', [3, 4])->get();
    Delegate::factory()->approved()->create([
        'seat_id' => $occupiedSeat->id,
        'organization_id' => $organization->id,
    ]);

    $service->sync($organization, [$college->id => ['delegates_per_seat' => 1, 'seats' => 3]]);

    expect(seatNumbersOf($collegeOrganization))->toBe([1, 2, 5]);
    $this->assertModelExists($occupiedSeat);
    $removedSeats->each(fn (Seat $seat) => $this->assertSoftDeleted($seat));
});

it('leaves the seats untouched when the seat count is unchanged', function () {
    $college = College::factory()->create();
    $organization = Organization::factory()->create();
    $service = new OrganizationCollegeSyncService;
    $service->sync($organization, [$college->id => ['delegates_per_seat' => 1, 'seats' => 3]]);
    $seatIds = Seat::orderBy('seat_number')->pluck('id')->all();

    $service->sync($organization, [$college->id => ['delegates_per_seat' => 1, 'seats' => 3]]);

    expect(Seat::orderBy('seat_number')->pluck('id')->all())->toBe($seatIds);
});

it('rejects removing more seats than there are empty ones and keeps every seat', function () {
    app()->setLocale('en');
    $college = College::factory()->create();
    $organization = Organization::factory()->create();
    $service = new OrganizationCollegeSyncService;
    $service->sync($organization, [$college->id => ['delegates_per_seat' => 1, 'seats' => 3]]);
    $collegeOrganization = collegeOrganizationFor($organization, $college);
    $collegeOrganization->seatRecords->each(
        fn (Seat $seat): Delegate => Delegate::factory()->approved()->create([
            'seat_id' => $seat->id,
            'organization_id' => $organization->id,
        ])
    );

    expect(fn () => $service->sync($organization, [$college->id => ['delegates_per_seat' => 1, 'seats' => 1]]))
        ->toThrow(
            Exception::class,
            "College {$college->name} has only 0 empty seat(s), but you requested to remove 2. Free occupied seats first."
        );

    expect(seatNumbersOf($collegeOrganization))->toBe([1, 2, 3]);
});

it('detaches colleges that are absent from the data', function () {
    $college1 = College::factory()->create();
    $college2 = College::factory()->create();
    $college3 = College::factory()->create();
    $organization = Organization::factory()->create();
    $service = new OrganizationCollegeSyncService;
    $service->sync($organization, [
        $college1->id => ['delegates_per_seat' => 1, 'seats' => 2],
        $college2->id => ['delegates_per_seat' => 1, 'seats' => 2],
        $college3->id => ['delegates_per_seat' => 1, 'seats' => 2],
    ]);

    $service->sync($organization, [
        $college1->id => ['delegates_per_seat' => 1, 'seats' => 2],
        $college2->id => ['delegates_per_seat' => 1, 'seats' => 2],
    ]);

    expect($organization->fresh()->colleges->pluck('id')->all())
        ->toEqualCanonicalizing([$college1->id, $college2->id]);
});

it('updates delegates per seat on an already attached college', function () {
    $college = College::factory()->create();
    $organization = Organization::factory()->create();
    $service = new OrganizationCollegeSyncService;
    $service->sync($organization, [$college->id => ['delegates_per_seat' => 1, 'seats' => 2]]);

    $service->sync($organization, [$college->id => ['delegates_per_seat' => 3, 'seats' => 2]]);

    expect(collegeOrganizationFor($organization, $college)->delegates_per_seat)->toBe(3);
});

it('rolls back every college when a later college fails', function () {
    $college1 = College::factory()->create();
    $college2 = College::factory()->create();
    $organization = Organization::factory()->create();
    $service = new OrganizationCollegeSyncService;
    $service->sync($organization, [
        $college1->id => ['delegates_per_seat' => 1, 'seats' => 2],
        $college2->id => ['delegates_per_seat' => 1, 'seats' => 2],
    ]);
    $collegeOrganization1 = collegeOrganizationFor($organization, $college1);
    $collegeOrganization2 = collegeOrganizationFor($organization, $college2);
    $collegeOrganization2->seatRecords->each(
        fn (Seat $seat): Delegate => Delegate::factory()->approved()->create([
            'seat_id' => $seat->id,
            'organization_id' => $organization->id,
        ])
    );

    expect(fn () => $service->sync($organization, [
        $college1->id => ['delegates_per_seat' => 4, 'seats' => 6],
        $college2->id => ['delegates_per_seat' => 1, 'seats' => 0],
    ]))->toThrow(Exception::class);

    expect(seatNumbersOf($collegeOrganization1))->toBe([1, 2])
        ->and($collegeOrganization1->fresh()->delegates_per_seat)->toBe(1)
        ->and(seatNumbersOf($collegeOrganization2))->toBe([1, 2]);
    $this->assertDatabaseCount('seats', 4);
});

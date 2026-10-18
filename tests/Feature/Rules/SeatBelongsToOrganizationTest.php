<?php

declare(strict_types=1);

use App\Models\CollegeOrganization;
use App\Models\Organization;
use App\Rules\SeatBelongsToOrganization;
use Illuminate\Support\Facades\Validator;

function seatValidationPasses(mixed $seatId, int|string|null $organizationId): bool
{
    return Validator::make(['seat_id' => $seatId], ['seat_id' => [new SeatBelongsToOrganization($organizationId)]])->passes();
}

it('passes when the seat belongs to the organization', function () {
    $collegeOrganization = CollegeOrganization::factory()->withSeats(1)->create();
    $seat = $collegeOrganization->seatRecords()->firstOrFail();

    expect(seatValidationPasses($seat->id, $collegeOrganization->organization_id))->toBeTrue();
});

it('fails when the seat belongs to another organization', function () {
    $collegeOrganization = CollegeOrganization::factory()->withSeats(1)->create();
    $seat = $collegeOrganization->seatRecords()->firstOrFail();
    $otherOrganization = Organization::factory()->create();

    expect(seatValidationPasses($seat->id, $otherOrganization->id))->toBeFalse();
});

it('fails with the exists message for an unknown seat or missing organization', function () {
    $collegeOrganization = CollegeOrganization::factory()->withSeats(1)->create();
    $seat = $collegeOrganization->seatRecords()->firstOrFail();

    $validator = Validator::make(['seat_id' => $seat->id], ['seat_id' => [new SeatBelongsToOrganization(null)]]);

    expect($validator->passes())->toBeFalse()
        ->and($validator->errors()->first('seat_id'))->toBe(__('validation.exists', ['attribute' => 'seat id']));
});

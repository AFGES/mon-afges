<?php

declare(strict_types=1);

use App\Models\College;
use App\Models\CollegeOrganization;
use App\Models\Seat;
use App\Rules\ProxyDelegatesFromSameCollege;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

function validateProxyHolderSeat(?string $delegatorSeatId, string $proxyHolderSeatId): Illuminate\Validation\Validator
{
    return Validator::make(
        ['proxy_holder_seat_id' => $proxyHolderSeatId],
        ['proxy_holder_seat_id' => [new ProxyDelegatesFromSameCollege($delegatorSeatId)]]
    );
}

it('passes for seats from the same college when the college requires it', function (): void {
    $college = College::factory()->create(['can_vote' => true, 'require_same_college_for_proxy' => true]);
    $collegeOrganization = CollegeOrganization::factory()->for($college)->create();
    $delegatorSeat = Seat::factory()->for($collegeOrganization)->create();
    $proxyHolderSeat = Seat::factory()->for($collegeOrganization)->create();

    $validator = validateProxyHolderSeat($delegatorSeat->id, $proxyHolderSeat->id);

    expect($validator->passes())->toBeTrue();
});

it('fails for seats from different colleges when the college requires the same college', function (): void {
    $delegatorSeat = Seat::factory()->for(CollegeOrganization::factory()->for(College::factory()->create(['require_same_college_for_proxy' => true])))->create();
    $proxyHolderSeat = Seat::factory()->create();

    $validator = validateProxyHolderSeat($delegatorSeat->id, $proxyHolderSeat->id);

    expect($validator->errors()->first('proxy_holder_seat_id'))
        ->toBe('Le siège mandataire doit appartenir au même collège que le siège délégateur.');
});

it('passes for a proxy holder from another voting college when the same college is not required', function (): void {
    $delegatorCollege = College::factory()->create(['require_same_college_for_proxy' => false, 'can_vote' => true]);
    $proxyHolderCollege = College::factory()->create(['can_vote' => true]);
    $delegatorSeat = Seat::factory()->for(CollegeOrganization::factory()->for($delegatorCollege))->create();
    $proxyHolderSeat = Seat::factory()->for(CollegeOrganization::factory()->for($proxyHolderCollege))->create();

    $validator = validateProxyHolderSeat($delegatorSeat->id, $proxyHolderSeat->id);

    expect($validator->passes())->toBeTrue();
});

it('fails for a proxy holder from a college that cannot vote when the same college is not required', function (): void {
    $delegatorCollege = College::factory()->create(['require_same_college_for_proxy' => false, 'can_vote' => true]);
    $proxyHolderCollege = College::factory()->create(['can_vote' => false]);
    $delegatorSeat = Seat::factory()->for(CollegeOrganization::factory()->for($delegatorCollege))->create();
    $proxyHolderSeat = Seat::factory()->for(CollegeOrganization::factory()->for($proxyHolderCollege))->create();

    $validator = validateProxyHolderSeat($delegatorSeat->id, $proxyHolderSeat->id);

    expect($validator->errors()->first('proxy_holder_seat_id'))
        ->toBe('Le siège mandataire doit appartenir à un collège délibérant.');
});

it('fails with the validation.exists message when the proxy holder seat does not exist', function (): void {
    $delegatorSeat = Seat::factory()->create();

    $validator = validateProxyHolderSeat($delegatorSeat->id, (string) Str::uuid());

    expect($validator->errors()->first('proxy_holder_seat_id'))->toBe('Le champ proxy holder seat id sélectionné est invalide.');
});

it('fails with the validation.exists message when the delegator seat does not exist', function (): void {
    $proxyHolderSeat = Seat::factory()->create();

    $validator = validateProxyHolderSeat((string) Str::uuid(), $proxyHolderSeat->id);

    expect($validator->errors()->first('proxy_holder_seat_id'))->toBe('Le champ proxy holder seat id sélectionné est invalide.');
});

it('skips validation when no delegator seat is given', function (): void {
    $validator = validateProxyHolderSeat(null, (string) Str::uuid());

    expect($validator->passes())->toBeTrue();
});

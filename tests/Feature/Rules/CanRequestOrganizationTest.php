<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;
use App\Models\Volunteer;
use App\Rules\CanRequestOrganization;
use Illuminate\Support\Facades\Validator;

function organizationRequestValidator(User $user, mixed $organizationId): Illuminate\Validation\Validator
{
    return Validator::make(['organization_id' => $organizationId], ['organization_id' => [new CanRequestOrganization($user->linkedOrganizationIds(), $user->pendingOrganizationRequest())]]);
}

it('passes when no request is pending', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    expect(organizationRequestValidator($user, $organization->id)->passes())->toBeTrue();
});

it('fails with the pending organization name when a request for a new organization is pending', function () {
    $user = User::factory()->create();
    $pendingOrganization = Organization::factory()->create(['short_name' => 'AFGES']);
    Volunteer::factory()->pending()->for($user)->for($pendingOrganization)->create();
    $otherOrganization = Organization::factory()->create();

    $validator = organizationRequestValidator($user, $otherOrganization->id);

    expect($validator->passes())->toBeFalse()
        ->and($validator->errors()->first('organization_id'))
        ->toBe(__('profile.manage_volunteers.request_locked.message', ['organization' => 'AFGES']));
});

it('fails for the pending organization itself', function () {
    $user = User::factory()->create();
    $pendingOrganization = Organization::factory()->create();
    Volunteer::factory()->pending()->for($user)->for($pendingOrganization)->create();

    expect(organizationRequestValidator($user, $pendingOrganization->id)->passes())->toBeFalse();
});

it('passes for a linked organization while a request is pending', function () {
    $user = User::factory()->create();
    $linkedOrganization = Organization::factory()->create();
    Volunteer::factory()->approved()->for($user)->for($linkedOrganization)->create();
    Volunteer::factory()->pending()->for($user)->create();

    expect(organizationRequestValidator($user, $linkedOrganization->id)->passes())->toBeTrue();
});

it('does not lock on a pending request for an already linked organization', function () {
    $user = User::factory()->create();
    $linkedOrganization = Organization::factory()->create();
    Volunteer::factory()->approved()->for($user)->for($linkedOrganization)->create();
    Volunteer::factory()->pending()->for($user)->for($linkedOrganization)->create();
    $otherOrganization = Organization::factory()->create();

    expect(organizationRequestValidator($user, $otherOrganization->id)->passes())->toBeTrue();
});

it('does not lock on a rejected request', function () {
    $user = User::factory()->create();
    Volunteer::factory()->rejected()->for($user)->create();
    $otherOrganization = Organization::factory()->create();

    expect(organizationRequestValidator($user, $otherOrganization->id)->passes())->toBeTrue();
});

it('does not lock on a pending request of another user', function () {
    $user = User::factory()->create();
    Volunteer::factory()->pending()->create();
    $otherOrganization = Organization::factory()->create();

    expect(organizationRequestValidator($user, $otherOrganization->id)->passes())->toBeTrue();
});

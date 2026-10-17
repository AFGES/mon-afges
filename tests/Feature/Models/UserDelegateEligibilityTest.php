<?php

declare(strict_types=1);

use App\Models\CollegeOrganization;
use App\Models\Seat;
use App\Models\User;
use App\Models\Volunteer;
use Illuminate\Support\Facades\DB;

test('can request a delegate seat when approved volunteer of an organization with a seat', function () {
    $user = User::factory()->create();
    $collegeOrganization = CollegeOrganization::factory()->create();
    Seat::factory()->create(['college_organization_id' => $collegeOrganization->id]);
    Volunteer::factory()->approved()->create([
        'user_id' => $user->id,
        'organization_id' => $collegeOrganization->organization_id,
    ]);

    expect($user->canRequestDelegateSeat())->toBeTrue()
        ->and(User::factory()->create()->canRequestDelegateSeat())->toBeFalse();
});

test('runs the eligibility query once for repeated calls on the same user instance', function () {
    $user = User::factory()->create();

    DB::enableQueryLog();

    $user->canRequestDelegateSeat();
    $user->canRequestDelegateSeat();
    $user->canRequestDelegateSeat();

    expect(DB::getQueryLog())->toHaveCount(1);
});

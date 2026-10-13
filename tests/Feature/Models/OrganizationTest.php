<?php

declare(strict_types=1);

use App\Models\College;
use App\Models\CollegeOrganization;
use App\Models\Organization;

test('creates the college link and its seats when syncing colleges with seats', function () {
    $organization = Organization::factory()->create();
    $college = College::factory()->create();

    $organization->syncCollegesWithSeats([
        $college->id => ['delegates_per_seat' => 2, 'seats' => 5],
    ]);

    $collegeOrganization = CollegeOrganization::query()
        ->where('organization_id', $organization->id)
        ->where('college_id', $college->id)
        ->sole();

    expect($organization->colleges->pluck('id')->all())->toBe([$college->id])
        ->and($collegeOrganization)
        ->seats->toBe(5)
        ->delegates_per_seat->toBe(2);
});

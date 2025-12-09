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

    $organization->colleges()->attach($college1->id, [
        'seats' => 3,
        'delegates_per_seat' => 2,
    ]);

    $organization->colleges()->attach($college2->id, [
        'seats' => 5,
        'delegates_per_seat' => 1,
    ]);

    expect($organization->colleges)->toHaveCount(2)
        ->and($organization->colleges->first()->pivot->seats)->toBe(3)
        ->and($organization->colleges->first()->pivot->delegates_per_seat)->toBe(2);
});

test('organization respects college max seats per organization', function () {
    $college = College::factory()->create([
        'name' => 'Limited College',
        'max_seats_per_organization' => 5,
    ]);

    $organization = Organization::factory()->create();

    // Within limit
    $organization->colleges()->attach($college->id, [
        'seats' => 3,
        'delegates_per_seat' => 1,
    ]);

    $pivot = $organization->colleges()->first()->pivot;

    expect($pivot->seats)->toBe(3)
        ->and($pivot->seats)->toBeLessThanOrEqual($college->max_seats_per_organization);
});

test('organization respects college max delegates per seat', function () {
    $college = College::factory()->create([
        'name' => 'Limited Delegates College',
        'max_delegates_per_seat' => 3,
    ]);

    $organization = Organization::factory()->create();

    // Within limit
    $organization->colleges()->attach($college->id, [
        'seats' => 2,
        'delegates_per_seat' => 2,
    ]);

    $pivot = $organization->colleges()->first()->pivot;

    expect($pivot->delegates_per_seat)->toBe(2)
        ->and($pivot->delegates_per_seat)->toBeLessThanOrEqual($college->max_delegates_per_seat);
});

test('organization can update college associations', function () {
    $college = College::factory()->create();
    $organization = Organization::factory()->create();

    // Initial association
    $organization->colleges()->attach($college->id, [
        'seats' => 2,
        'delegates_per_seat' => 1,
    ]);

    expect($organization->colleges()->first()->pivot->seats)->toBe(2);

    // Update association
    $organization->colleges()->updateExistingPivot($college->id, [
        'seats' => 4,
        'delegates_per_seat' => 2,
    ]);

    expect($organization->fresh()->colleges()->first()->pivot->seats)->toBe(4)
        ->and($organization->fresh()->colleges()->first()->pivot->delegates_per_seat)->toBe(2);
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

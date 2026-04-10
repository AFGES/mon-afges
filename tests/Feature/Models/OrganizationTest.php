<?php

declare(strict_types=1);

use App\Models\College;
use App\Models\Organization;

test('organization has short name', function () {
    $organization = Organization::factory()->create(['short_name' => 'AFGES']);

    expect($organization->short_name)->toBe('AFGES');
});

test('organization has legal name', function () {
    $organization = Organization::factory()->create(['legal_name' => 'Association Fédérale Générale des Étudiants de Strasbourg']);

    expect($organization->legal_name)->toBe('Association Fédérale Générale des Étudiants de Strasbourg');
});

test('organization can have multiple colleges', function () {
    $organization = Organization::factory()->create();
    $college1 = College::factory()->create();
    $college2 = College::factory()->create();
    $college3 = College::factory()->create();

    $organization->syncCollegesWithSeats([
        $college1->id => ['delegates_per_seat' => 1, 'seats' => 2],
        $college2->id => ['delegates_per_seat' => 2, 'seats' => 3],
        $college3->id => ['delegates_per_seat' => 1, 'seats' => 1],
    ]);

    expect($organization->fresh()->colleges)->toHaveCount(3);
});

test('organization can be soft deleted', function () {
    $organization = Organization::factory()->create();

    expect(Organization::count())->toBe(1);

    $organization->delete();

    expect(Organization::count())->toBe(0)
        ->and(Organization::withTrashed()->count())->toBe(1);
});

test('organization can be restored after soft delete', function () {
    $organization = Organization::factory()->create();

    $organization->delete();
    expect(Organization::count())->toBe(0);

    $organization->restore();
    expect(Organization::count())->toBe(1);
});

test('organization sync colleges with seats uses service', function () {
    $organization = Organization::factory()->create();
    $college = College::factory()->create();

    $organization->syncCollegesWithSeats([
        $college->id => ['delegates_per_seat' => 2, 'seats' => 5],
    ]);

    expect($organization->colleges)->toHaveCount(1);
});

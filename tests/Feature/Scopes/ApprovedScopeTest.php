<?php

declare(strict_types=1);

use App\Models\Delegate;
use App\Models\Scopes\ApprovedScope;
use App\Models\Volunteer;

dataset('models with the approved scope', [
    'delegate' => Delegate::class,
    'volunteer' => Volunteer::class,
]);

test('returns only approved records by default', function (string $model) {
    $model::factory()->pending()->create();
    $model::factory()->rejected()->create();
    $approved = $model::factory()->approved()->create();

    $visibleIds = $model::query()->pluck('id');

    expect($visibleIds->all())->toBe([$approved->id]);
})->with('models with the approved scope');

test('includes records of every status when the approved scope is removed', function (string $model) {
    $model::factory()->pending()->create();
    $model::factory()->rejected()->create();
    $model::factory()->approved()->create();

    $count = $model::withoutGlobalScope(ApprovedScope::class)->count();

    expect($count)->toBe(3);
})->with('models with the approved scope');

<?php

declare(strict_types=1);

use App\Models\Delegate;

use function Pest\Laravel\travelTo;

beforeEach(function () {
    travelTo('2026-03-15 12:00:00');
});

test('returns only delegates that have started and not ended', function () {
    $noEndDate = Delegate::factory()->approved()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => null,
    ]);
    $withFutureEndDate = Delegate::factory()->approved()->create([
        'started_at' => now()->subDays(10),
        'ended_at' => now()->addDays(5),
    ]);
    Delegate::factory()->approved()->create([
        'started_at' => now()->addDays(5),
        'ended_at' => null,
    ]);
    Delegate::factory()->approved()->create([
        'started_at' => now()->subDays(20),
        'ended_at' => now()->subDays(5),
    ]);

    $currentDelegates = Delegate::all();

    expect($currentDelegates->pluck('id')->all())
        ->toEqualCanonicalizing([$noEndDate->id, $withFutureEndDate->id]);
});

test('includes delegates that end today', function () {
    $delegate = Delegate::factory()->approved()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => now(),
    ]);

    $currentDelegates = Delegate::all();

    expect($currentDelegates->pluck('id')->all())->toBe([$delegate->id]);
});

test('excludes delegates that ended yesterday', function () {
    Delegate::factory()->approved()->create([
        'started_at' => now()->subDays(10),
        'ended_at' => now()->subDay(),
    ]);

    $currentDelegates = Delegate::all();

    expect($currentDelegates)->toBeEmpty();
});

test('excludes delegates that start tomorrow', function () {
    Delegate::factory()->approved()->create([
        'started_at' => now()->addDay(),
        'ended_at' => null,
    ]);

    $currentDelegates = Delegate::all();

    expect($currentDelegates)->toBeEmpty();
});

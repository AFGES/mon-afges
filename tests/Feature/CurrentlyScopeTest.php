<?php

declare(strict_types=1);

use App\Models\Delegate;

it('filters delegates that have started but not ended', function () {
    Delegate::factory()->approved()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => null,
    ]);

    Delegate::factory()->approved()->create([
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

    expect($currentDelegates)->toHaveCount(2);
});

it('includes delegates that end today', function () {
    Delegate::factory()->approved()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => now(),
    ]);

    $currentDelegates = Delegate::all();

    expect($currentDelegates)->toHaveCount(1);
});

it('excludes delegates that ended yesterday', function () {
    Delegate::factory()->approved()->create([
        'started_at' => now()->subDays(10),
        'ended_at' => now()->subDays(1),
    ]);

    $currentDelegates = Delegate::all();

    expect($currentDelegates)->toHaveCount(0);
});

it('excludes delegates that start tomorrow', function () {
    Delegate::factory()->approved()->create([
        'started_at' => now()->addDays(1),
        'ended_at' => null,
    ]);

    $currentDelegates = Delegate::all();

    expect($currentDelegates)->toHaveCount(0);
});

it('can be bypassed with withoutGlobalScope', function () {
    Delegate::factory()->approved()->create([
        'started_at' => now()->addDays(5),
        'ended_at' => null,
    ]);

    Delegate::factory()->approved()->create([
        'started_at' => now()->subDays(10),
        'ended_at' => now()->subDays(5),
    ]);

    $allDelegates = Delegate::withoutGlobalScope(\App\Models\Scopes\CurrentlyScope::class)->get();

    expect($allDelegates)->toHaveCount(2);
});

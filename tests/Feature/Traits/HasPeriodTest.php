<?php

declare(strict_types=1);

use App\Models\Delegate;
use App\Models\Volunteer;

it('determines if a record is currently active with no end date', function () {
    $delegate = Delegate::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => null,
    ]);

    expect($delegate->isCurrentlyActive())->toBeTrue();
});

it('determines if a record is currently active within period', function () {
    $delegate = Delegate::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => now()->addDays(5),
    ]);

    expect($delegate->isCurrentlyActive())->toBeTrue();
});

it('determines if a record is not active when start date is in future', function () {
    $delegate = Delegate::factory()->create([
        'started_at' => now()->addDays(5),
        'ended_at' => null,
    ]);

    expect($delegate->isCurrentlyActive())->toBeFalse();
});

it('determines if a record is not active when ended', function () {
    $delegate = Delegate::factory()->create([
        'started_at' => now()->subDays(10),
        'ended_at' => now()->subDays(2),
    ]);

    expect($delegate->isCurrentlyActive())->toBeFalse();
});

it('considers record active on end date', function () {
    $delegate = Delegate::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => now(),
    ]);

    expect($delegate->isCurrentlyActive())->toBeTrue();
});

it('determines if record has ended', function () {
    $delegate = Delegate::factory()->create([
        'started_at' => now()->subDays(10),
        'ended_at' => now()->subDays(2),
    ]);

    expect($delegate->hasEnded())->toBeTrue();
});

it('determines record has not ended when end date is null', function () {
    $delegate = Delegate::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => null,
    ]);

    expect($delegate->hasEnded())->toBeFalse();
});

it('determines record has not ended when end date is in future', function () {
    $delegate = Delegate::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => now()->addDays(5),
    ]);

    expect($delegate->hasEnded())->toBeFalse();
});

it('can end a record with default date', function () {
    $delegate = Delegate::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => null,
    ]);

    $result = $delegate->end();

    expect($result)->toBeTrue();
    expect($delegate->fresh()->ended_at)->not->toBeNull();
    expect($delegate->fresh()->ended_at->isToday())->toBeTrue();
});

it('can end a record with specific date', function () {
    $delegate = Delegate::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => null,
    ]);

    $endDate = now()->addDays(10);
    $result = $delegate->end($endDate);

    expect($result)->toBeTrue();
    expect($delegate->fresh()->ended_at->isSameDay($endDate))->toBeTrue();
});

it('cannot end a record that already ended', function () {
    $delegate = Delegate::factory()->create([
        'started_at' => now()->subDays(10),
        'ended_at' => now()->subDays(2),
    ]);

    $result = $delegate->end();

    expect($result)->toBeFalse();
});

it('works with Volunteer model', function () {
    $volunteer = Volunteer::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => null,
    ]);

    expect($volunteer->isCurrentlyActive())->toBeTrue();
    expect($volunteer->hasEnded())->toBeFalse();

    $volunteer->end(now()->subDays(1));

    expect($volunteer->fresh()->hasEnded())->toBeTrue();
    expect($volunteer->fresh()->isCurrentlyActive())->toBeFalse();
});

it('initializes fillable attributes', function () {
    $delegate = new Delegate;

    expect($delegate->getFillable())->toContain('started_at', 'ended_at');
});

it('has proper casts for dates', function () {
    $delegate = Delegate::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => now()->addDays(5),
    ]);

    expect($delegate->started_at)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($delegate->ended_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

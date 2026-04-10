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

    expect($delegate->started_at)->toBeInstanceOf(Carbon\Carbon::class);
    expect($delegate->ended_at)->toBeInstanceOf(Carbon\Carbon::class);
});

it('currentlyActive scope filters active records', function () {
    // Currently active with no end date
    $active1 = Volunteer::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => null,
    ]);

    // Currently active within period
    $active2 = Volunteer::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => now()->addDays(5),
    ]);

    // Active on end date (today)
    $active3 = Volunteer::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => now(),
    ]);

    // Not started yet
    $notStarted = Volunteer::factory()->create([
        'started_at' => now()->addDays(5),
        'ended_at' => null,
    ]);

    // Ended
    $ended = Volunteer::factory()->create([
        'started_at' => now()->subDays(10),
        'ended_at' => now()->subDays(2),
    ]);

    $results = Volunteer::withoutGlobalScopes()
        ->currentlyActive()
        ->get();

    expect($results)->toHaveCount(3);
    expect($results->pluck('id'))->toContain($active1->id, $active2->id, $active3->id);
    expect($results->pluck('id'))->not->toContain($notStarted->id, $ended->id);
});

it('ended scope filters ended records', function () {
    // Ended yesterday
    $ended1 = Volunteer::factory()->create([
        'started_at' => now()->subDays(10),
        'ended_at' => now()->subDays(1),
    ]);

    // Ended long ago
    $ended2 = Volunteer::factory()->create([
        'started_at' => now()->subDays(20),
        'ended_at' => now()->subDays(5),
    ]);

    // Still active (ends today)
    $activeToday = Volunteer::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => now(),
    ]);

    // Still active (no end date)
    $activeNoEnd = Volunteer::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => null,
    ]);

    // Future end date
    $activeFuture = Volunteer::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => now()->addDays(5),
    ]);

    $results = Volunteer::withoutGlobalScopes()
        ->ended()
        ->get();

    expect($results)->toHaveCount(2);
    expect($results->pluck('id'))->toContain($ended1->id, $ended2->id);
    expect($results->pluck('id'))->not->toContain($activeToday->id, $activeNoEnd->id, $activeFuture->id);
});

it('notStarted scope filters future records', function () {
    // Not started yet
    $future1 = Volunteer::factory()->create([
        'started_at' => now()->addDays(1),
        'ended_at' => null,
    ]);

    $future2 = Volunteer::factory()->create([
        'started_at' => now()->addDays(5),
        'ended_at' => now()->addDays(10),
    ]);

    // Already started
    $started = Volunteer::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => null,
    ]);

    // Started today
    $startedToday = Volunteer::factory()->create([
        'started_at' => now(),
        'ended_at' => null,
    ]);

    $results = Volunteer::withoutGlobalScopes()
        ->notStarted()
        ->get();

    expect($results)->toHaveCount(2);
    expect($results->pluck('id'))->toContain($future1->id, $future2->id);
    expect($results->pluck('id'))->not->toContain($started->id, $startedToday->id);
});

it('startedBy scope filters records started before or on date', function () {
    $targetDate = now()->subDays(5);

    $before = Volunteer::factory()->create([
        'started_at' => $targetDate->copy()->subDays(2),
        'ended_at' => null,
    ]);

    $onDate = Volunteer::factory()->create([
        'started_at' => $targetDate,
        'ended_at' => null,
    ]);

    $after = Volunteer::factory()->create([
        'started_at' => $targetDate->copy()->addDays(2),
        'ended_at' => null,
    ]);

    $results = Volunteer::withoutGlobalScopes()
        ->startedBy($targetDate)
        ->get();

    expect($results)->toHaveCount(2);
    expect($results->pluck('id'))->toContain($before->id, $onDate->id);
    expect($results->pluck('id'))->not->toContain($after->id);
});

it('endedAfter scope filters records ended on or after date', function () {
    $targetDate = now()->subDays(5);

    $before = Volunteer::factory()->create([
        'started_at' => now()->subDays(10),
        'ended_at' => $targetDate->copy()->subDays(2),
    ]);

    $onDate = Volunteer::factory()->create([
        'started_at' => now()->subDays(10),
        'ended_at' => $targetDate,
    ]);

    $after = Volunteer::factory()->create([
        'started_at' => now()->subDays(10),
        'ended_at' => $targetDate->copy()->addDays(2),
    ]);

    $noEndDate = Volunteer::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => null,
    ]);

    $results = Volunteer::withoutGlobalScopes()
        ->endedAfter($targetDate)
        ->get();

    expect($results)->toHaveCount(2);
    expect($results->pluck('id'))->toContain($onDate->id, $after->id);
    expect($results->pluck('id'))->not->toContain($before->id, $noEndDate->id);
});

it('scopes can be combined', function () {
    // Currently active, started before target date
    $match = Volunteer::factory()->create([
        'started_at' => now()->subDays(10),
        'ended_at' => now()->addDays(5),
    ]);

    // Currently active, started after target date
    $recentActive = Volunteer::factory()->create([
        'started_at' => now()->subDays(2),
        'ended_at' => null,
    ]);

    // Started before target, but ended
    $oldEnded = Volunteer::factory()->create([
        'started_at' => now()->subDays(10),
        'ended_at' => now()->subDays(3),
    ]);

    $results = Volunteer::withoutGlobalScopes()
        ->currentlyActive()
        ->startedBy(now()->subDays(5))
        ->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($match->id);
});

<?php

declare(strict_types=1);

use App\Models\Delegate;
use App\Models\Volunteer;

use function Pest\Laravel\travelTo;

dataset('period models', [
    'delegate' => Delegate::class,
    'volunteer' => Volunteer::class,
]);

beforeEach(function () {
    travelTo('2026-03-15 12:00:00');
});

test('is currently active when it has started and has no end date', function (string $model) {
    $record = $model::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => null,
    ]);

    expect($record)
        ->isCurrentlyActive()->toBeTrue()
        ->hasEnded()->toBeFalse();
})->with('period models');

test('is currently active when today is within its period', function (string $model) {
    $record = $model::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => now()->addDays(5),
    ]);

    expect($record)
        ->isCurrentlyActive()->toBeTrue()
        ->hasEnded()->toBeFalse();
})->with('period models');

test('is currently active on its end date', function (string $model) {
    $record = $model::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => now(),
    ]);

    expect($record)
        ->isCurrentlyActive()->toBeTrue()
        ->hasEnded()->toBeFalse();
})->with('period models');

test('is not active when its start date is in the future', function (string $model) {
    $record = $model::factory()->create([
        'started_at' => now()->addDays(5),
        'ended_at' => null,
    ]);

    expect($record->isCurrentlyActive())->toBeFalse();
})->with('period models');

test('is not active and has ended once its end date has passed', function (string $model) {
    $record = $model::factory()->create([
        'started_at' => now()->subDays(10),
        'ended_at' => now()->subDays(2),
    ]);

    expect($record)
        ->isCurrentlyActive()->toBeFalse()
        ->hasEnded()->toBeTrue();
})->with('period models');

test('ends with the current time by default', function (string $model) {
    $record = $model::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => null,
    ]);

    $result = $record->end();

    expect($result)->toBeTrue()
        ->and($record->refresh()->ended_at->toDateString())->toBe('2026-03-15');
})->with('period models');

test('ends on the given date', function (string $model) {
    $record = $model::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => null,
    ]);

    $result = $record->end(now()->addDays(10));

    expect($result)->toBeTrue()
        ->and($record->refresh()->ended_at->toDateString())->toBe('2026-03-25');
})->with('period models');

test('does not end a record that has already ended', function (string $model) {
    $record = $model::factory()->create([
        'started_at' => now()->subDays(10),
        'ended_at' => '2026-03-10',
    ]);

    $result = $record->end();

    expect($result)->toBeFalse()
        ->and($record->refresh()->ended_at->toDateString())->toBe('2026-03-10');
})->with('period models');

test('reads delegate dates without time but volunteer dates with time', function () {
    $delegate = Delegate::factory()->create(['started_at' => '2026-03-01 15:30:00', 'ended_at' => null]);
    $volunteer = Volunteer::factory()->create(['started_at' => '2026-03-01 15:30:00']);

    expect($delegate->refresh()->started_at->toDateTimeString())->toBe('2026-03-01 00:00:00')
        ->and($volunteer->refresh()->started_at->toDateTimeString())->toBe('2026-03-01 15:30:00');
});

test('currentlyActive scope returns records that have started and have not ended', function () {
    $noEndDate = Volunteer::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => null,
    ]);
    $withinPeriod = Volunteer::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => now()->addDays(5),
    ]);
    $endingToday = Volunteer::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => now(),
    ]);
    Volunteer::factory()->create([
        'started_at' => now()->addDays(5),
        'ended_at' => null,
    ]);
    Volunteer::factory()->create([
        'started_at' => now()->subDays(10),
        'ended_at' => now()->subDays(2),
    ]);

    $results = Volunteer::withoutGlobalScopes()->currentlyActive()->get();

    expect($results->pluck('id')->all())
        ->toEqualCanonicalizing([$noEndDate->id, $withinPeriod->id, $endingToday->id]);
});

test('ended scope returns only records whose end date is before today', function () {
    $endedYesterday = Volunteer::factory()->create([
        'started_at' => now()->subDays(10),
        'ended_at' => now()->subDay(),
    ]);
    $endedLongAgo = Volunteer::factory()->create([
        'started_at' => now()->subDays(20),
        'ended_at' => now()->subDays(5),
    ]);
    Volunteer::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => now(),
    ]);
    Volunteer::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => null,
    ]);
    Volunteer::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => now()->addDays(5),
    ]);

    $results = Volunteer::withoutGlobalScopes()->ended()->get();

    expect($results->pluck('id')->all())
        ->toEqualCanonicalizing([$endedYesterday->id, $endedLongAgo->id]);
});

test('notStarted scope returns only records starting after now', function () {
    $startingTomorrow = Volunteer::factory()->create([
        'started_at' => now()->addDay(),
        'ended_at' => null,
    ]);
    $startingNextWeek = Volunteer::factory()->create([
        'started_at' => now()->addDays(5),
        'ended_at' => now()->addDays(10),
    ]);
    Volunteer::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => null,
    ]);
    Volunteer::factory()->create([
        'started_at' => now(),
        'ended_at' => null,
    ]);

    $results = Volunteer::withoutGlobalScopes()->notStarted()->get();

    expect($results->pluck('id')->all())
        ->toEqualCanonicalizing([$startingTomorrow->id, $startingNextWeek->id]);
});

test('startedBy scope returns records started on or before the given date', function () {
    $targetDate = now()->subDays(5);
    $before = Volunteer::factory()->create([
        'started_at' => $targetDate->copy()->subDays(2),
        'ended_at' => null,
    ]);
    $onDate = Volunteer::factory()->create([
        'started_at' => $targetDate,
        'ended_at' => null,
    ]);
    Volunteer::factory()->create([
        'started_at' => $targetDate->copy()->addDays(2),
        'ended_at' => null,
    ]);

    $results = Volunteer::withoutGlobalScopes()->startedBy($targetDate)->get();

    expect($results->pluck('id')->all())->toEqualCanonicalizing([$before->id, $onDate->id]);
});

test('endedAfter scope returns records ended on or after the given date', function () {
    $targetDate = now()->subDays(5);
    Volunteer::factory()->create([
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
    Volunteer::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => null,
    ]);

    $results = Volunteer::withoutGlobalScopes()->endedAfter($targetDate)->get();

    expect($results->pluck('id')->all())->toEqualCanonicalizing([$onDate->id, $after->id]);
});

test('period scopes combine', function () {
    $match = Volunteer::factory()->create([
        'started_at' => now()->subDays(10),
        'ended_at' => now()->addDays(5),
    ]);
    Volunteer::factory()->create([
        'started_at' => now()->subDays(2),
        'ended_at' => null,
    ]);
    Volunteer::factory()->create([
        'started_at' => now()->subDays(10),
        'ended_at' => now()->subDays(3),
    ]);

    $results = Volunteer::withoutGlobalScopes()
        ->currentlyActive()
        ->startedBy(now()->subDays(5))
        ->get();

    expect($results->pluck('id')->all())->toBe([$match->id]);
});

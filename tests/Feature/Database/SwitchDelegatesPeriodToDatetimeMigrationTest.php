<?php

declare(strict_types=1);

use App\Models\Delegate;
use App\Models\Volunteer;
use Illuminate\Support\Facades\DB;

function switchDelegatesPeriodMigration(): object
{
    return require database_path('migrations/2026_10_20_083440_switch_delegates_period_to_datetime.php');
}

function seedPeriodRows(): array
{
    $delegate = Delegate::factory()->create(['started_at' => '2026-01-05 00:00:00', 'ended_at' => '2026-03-31 00:00:00']);
    $openDelegate = Delegate::factory()->create(['started_at' => '2026-01-05 00:00:00', 'ended_at' => null]);
    $midnightVolunteer = Volunteer::factory()->create(['started_at' => '2026-01-05 00:00:00', 'ended_at' => '2026-03-31 00:00:00']);
    $timedVolunteer = Volunteer::factory()->create(['started_at' => '2026-01-05 10:00:00', 'ended_at' => '2026-03-31 17:45:00']);

    return [$delegate, $openDelegate, $midnightVolunteer, $timedVolunteer];
}

function endedAtOf(string $table, string $id): ?string
{
    return DB::table($table)->where('id', $id)->value('ended_at');
}

it('moves inclusive end days to the start of the next day', function () {
    [$delegate, $openDelegate, $midnightVolunteer, $timedVolunteer] = seedPeriodRows();

    switchDelegatesPeriodMigration()->up();

    expect(endedAtOf('delegates', $delegate->id))->toBe('2026-04-01 00:00:00')
        ->and(endedAtOf('delegates', $openDelegate->id))->toBeNull()
        ->and(endedAtOf('volunteers', $midnightVolunteer->id))->toBe('2026-04-01 00:00:00')
        ->and(endedAtOf('volunteers', $timedVolunteer->id))->toBe('2026-03-31 17:45:00');
});

it('restores the inclusive end days when rolled back', function () {
    [$delegate, , $midnightVolunteer, $timedVolunteer] = seedPeriodRows();
    $migration = switchDelegatesPeriodMigration();

    $migration->up();
    $migration->down();

    expect(mb_substr(endedAtOf('delegates', $delegate->id), 0, 10))->toBe('2026-03-31')
        ->and(endedAtOf('volunteers', $midnightVolunteer->id))->toBe('2026-03-31 00:00:00')
        ->and(endedAtOf('volunteers', $timedVolunteer->id))->toBe('2026-03-31 17:45:00');
});

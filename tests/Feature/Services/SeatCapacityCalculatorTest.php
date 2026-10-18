<?php

declare(strict_types=1);

use App\Models\CollegeOrganization;
use App\Models\Delegate;
use App\Models\Seat;
use App\Services\SeatCapacityCalculator;

beforeEach(function () {
    mockGoogleWorkspaceService()->shouldIgnoreMissing();
});

function seatWithCapacityForCalculator(int $delegatesPerSeat): Seat
{
    return Seat::factory()->create([
        'college_organization_id' => CollegeOrganization::factory()->create(['delegates_per_seat' => $delegatesPerSeat])->id,
    ]);
}

it('lists the current approved delegates of the seat excluding the given delegate', function () {
    $seat = Seat::factory()->create();
    $current = Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->subYear(), 'ended_at' => null]);
    Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->subYears(3), 'ended_at' => now()->subYear()]);
    $pending = Delegate::factory()->pending()->create(['seat_id' => $seat->id]);

    $result = (new SeatCapacityCalculator)->currentDelegatesOfSeat($pending);

    expect($result->modelKeys())->toBe([$current->id]);
});

it('computes how many current delegates must end to fit the seat capacity', function (int $capacity, int $currentCount, int $expected) {
    $seat = seatWithCapacityForCalculator($capacity);
    Delegate::factory()->approved()->count($currentCount)->create(['seat_id' => $seat->id, 'started_at' => now()->subYear(), 'ended_at' => null]);
    $pending = Delegate::factory()->pending()->create(['seat_id' => $seat->id]);

    expect((new SeatCapacityCalculator)->minimumTerminations($pending))->toBe($expected);
})->with([
    'empty seat' => [1, 0, 0],
    'room left' => [3, 2, 0],
    'full seat' => [2, 2, 1],
    'over capacity' => [1, 3, 3],
]);

describe('capacity over the whole period', function () {
    it('counts approved delegates whose mandate starts in the future', function () {
        $seat = seatWithCapacityForCalculator(1);
        $future = Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->addMonth()->startOfDay(), 'ended_at' => null]);
        $pending = Delegate::factory()->pending()->create(['seat_id' => $seat->id, 'started_at' => now()->startOfDay(), 'ended_at' => null]);
        $service = new SeatCapacityCalculator;

        expect($service->minimumTerminations($pending))->toBe(1)
            ->and($service->currentDelegatesOfSeat($pending)->modelKeys())->toBe([$future->id]);
    });

    it('ignores approved delegates whose mandate does not overlap the period', function () {
        $seat = seatWithCapacityForCalculator(1);
        Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->addYear()->startOfDay(), 'ended_at' => null]);
        $pending = Delegate::factory()->pending()->create(['seat_id' => $seat->id, 'started_at' => now()->startOfDay(), 'ended_at' => now()->addMonth()->startOfDay()]);

        expect((new SeatCapacityCalculator)->minimumTerminations($pending))->toBe(0);
    });

    it('uses the peak of concurrent delegates so sequential mandates do not add up', function () {
        $seat = seatWithCapacityForCalculator(2);
        Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->subMonths(6)->startOfDay(), 'ended_at' => now()->addMonth()->startOfDay()]);
        Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->addMonths(2)->startOfDay(), 'ended_at' => now()->addMonths(4)->startOfDay()]);
        Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->addMonths(5)->startOfDay(), 'ended_at' => null]);
        $pending = Delegate::factory()->pending()->create(['seat_id' => $seat->id, 'started_at' => now()->startOfDay(), 'ended_at' => null]);

        expect((new SeatCapacityCalculator)->minimumTerminations($pending))->toBe(0);
    });

    it('reports the excess when a mandate overlaps the busiest moment', function () {
        $seat = seatWithCapacityForCalculator(2);
        Delegate::factory()->approved()->count(2)->create(['seat_id' => $seat->id, 'started_at' => now()->addMonth()->startOfDay(), 'ended_at' => null]);

        $violation = (new SeatCapacityCalculator)->capacityViolation($seat->id, now()->startOfDay(), null);

        expect($violation)->toBe(1);
    });
});

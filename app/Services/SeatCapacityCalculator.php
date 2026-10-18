<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Delegate;
use App\Models\Scopes\CurrentlyScope;
use App\Models\Seat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Read-only questions about how many delegates a seat holds and may hold.
 */
final class SeatCapacityCalculator
{
    /**
     * Number of delegates allowed to sit on the delegate's seat at the same time, as configured for its college.
     */
    public function seatCapacity(Delegate $delegate): int
    {
        return $this->seatCapacityForSeat((string) $delegate->seat_id);
    }

    /**
     * Number of delegates allowed to sit on the seat at the same time, as configured for its college.
     */
    public function seatCapacityForSeat(string $seatId): int
    {
        return Seat::query()->with('collegeOrganization')->findOrFail($seatId)->collegeOrganization->delegates_per_seat;
    }

    /**
     * How many approved delegates of the seat must be ended so the delegate fits within the seat capacity over its period.
     */
    public function minimumTerminations(Delegate $delegate): int
    {
        return $this->minimumTerminationsForSeat(
            (string) $delegate->seat_id,
            $delegate->getKey(),
            $delegate->started_at,
            $delegate->ended_at,
        );
    }

    /**
     * How many approved delegates of the seat must be ended so one more delegate fits within the seat capacity
     * over the given period (defaults to today, open-ended).
     */
    public function minimumTerminationsForSeat(string $seatId, ?string $exceptDelegateId = null, ?Carbon $startedAt = null, ?Carbon $endedAt = null): int
    {
        return $this->capacityViolation($seatId, $startedAt ?? now(), $endedAt, $exceptDelegateId);
    }

    /**
     * By how many delegates the seat exceeds its capacity if a delegate sat on it during the given period.
     *
     * The peak number of concurrent approved delegates over the period is used, so mandates that follow
     * one another never add up. Returns 0 when the seat can hold the delegate.
     */
    public function capacityViolation(string $seatId, Carbon $startedAt, ?Carbon $endedAt, ?string $exceptDelegateId = null): int
    {
        $delegates = $this->currentDelegatesOfSeatId($seatId, $exceptDelegateId, $startedAt, $endedAt);

        return $this->peakViolation($delegates, $startedAt, $this->seatCapacityForSeat($seatId), []);
    }

    /**
     * Approved, current or future delegates occupying the seat of the given delegate during its period, excluding the delegate itself.
     *
     * @return Collection<int, Delegate>
     */
    public function currentDelegatesOfSeat(Delegate $delegate): Collection
    {
        return $this->currentDelegatesOfSeatId(
            (string) $delegate->seat_id,
            $delegate->getKey(),
            $delegate->started_at,
            $delegate->ended_at,
        );
    }

    /**
     * Approved delegates of the seat whose mandate overlaps the given period (defaults to today, open-ended),
     * so current and future ones, oldest first, optionally excluding one delegate.
     *
     * @return Collection<int, Delegate>
     */
    public function currentDelegatesOfSeatId(string $seatId, ?string $exceptDelegateId = null, ?Carbon $startedAt = null, ?Carbon $endedAt = null): Collection
    {
        $start = ($startedAt ?? now())->copy()->startOfDay();
        $end = $endedAt?->copy()->startOfDay();

        return Delegate::query()
            ->withoutGlobalScope(CurrentlyScope::class)
            ->with('user')
            ->where('seat_id', $seatId)
            ->when($exceptDelegateId !== null, fn (Builder $query): Builder => $query->whereKeyNot($exceptDelegateId))
            ->oldest('started_at')
            ->get()
            ->filter(fn (Delegate $other): bool => ($end === null || $other->started_at->copy()->startOfDay()->lte($end))
                && ($other->ended_at === null || $other->ended_at->copy()->startOfDay()->gte($start)))
            ->values();
    }

    /**
     * Number of delegates over capacity at the busiest moment from the start date on once the new delegate is added.
     *
     * @param  Collection<int, Delegate>  $delegates  Approved delegates overlapping the period.
     * @param  list<string>  $excludedDelegateIds  Delegates ignored because they will be ended.
     */
    public function peakViolation(Collection $delegates, Carbon $startedAt, int $capacity, array $excludedDelegateIds): int
    {
        $start = $startedAt->copy()->startOfDay();
        $kept = $delegates->whereNotIn('id', $excludedDelegateIds);

        $checkpoints = $kept
            ->map(fn (Delegate $delegate): Carbon => $delegate->started_at->copy()->startOfDay())
            ->push($start)
            ->filter(fn (Carbon $date): bool => $date->gte($start))
            ->unique(fn (Carbon $date): string => $date->toDateString());

        $peak = $checkpoints
            ->map(fn (Carbon $date): int => $kept->filter(
                fn (Delegate $delegate): bool => $delegate->started_at->copy()->startOfDay()->lte($date)
                    && ($delegate->ended_at === null || $delegate->ended_at->copy()->startOfDay()->gte($date))
            )->count())
            ->max() ?? 0;

        return max(0, $peak + 1 - $capacity);
    }
}

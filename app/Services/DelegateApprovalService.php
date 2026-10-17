<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Models\Delegate;
use App\Models\Scopes\CurrentlyScope;
use App\Models\Seat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DelegateApprovalService
{
    /**
     * Approve the delegate, ending the given approved delegates of the same seat first.
     *
     * Keeping other delegates is only allowed while the seat capacity set for the college permits it over the
     * whole period of the approved delegate. The seat row is locked meanwhile so concurrent approvals queue up.
     *
     * @param  list<string>  $terminatedDelegateIds
     *
     * @throws ValidationException When the seat would exceed its capacity after approval.
     */
    public function approve(Delegate $delegate, array $terminatedDelegateIds = []): void
    {
        DB::transaction(function () use ($delegate, $terminatedDelegateIds): void {
            $this->terminateForCapacity(
                (string) $delegate->seat_id,
                $delegate->started_at ?? now(),
                $delegate->ended_at,
                $delegate->getKey(),
                $terminatedDelegateIds,
            );

            $delegate->approve();
        });
    }

    /**
     * Create a delegate that is already approved by the authenticated user, ending the given approved delegates of its seat first.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $terminatedDelegateIds
     *
     * @throws ValidationException When the seat would exceed its capacity after creation.
     */
    public function createApproved(array $attributes, array $terminatedDelegateIds = []): Delegate
    {
        return DB::transaction(function () use ($attributes, $terminatedDelegateIds): Delegate {
            $this->terminateForCapacity(
                (string) $attributes['seat_id'],
                isset($attributes['started_at']) ? Carbon::parse($attributes['started_at']) : now(),
                isset($attributes['ended_at']) ? Carbon::parse($attributes['ended_at']) : null,
                null,
                $terminatedDelegateIds,
            );

            $delegate = new Delegate($attributes);
            $delegate->forceFill([
                'approval_status' => ApprovalStatus::Approved,
                'approved_at' => now(),
                'approved_by' => auth()->id(),
            ])->save();

            return $delegate;
        });
    }

    /**
     * Approve the delegate, ending every other approved delegate of its seat overlapping its period.
     *
     * Delegates listed in `$protectedDelegateIds` (for instance approved earlier in the same batch) are never ended;
     * approval then fails with a validation error when the seat cannot hold the delegate without ending them.
     *
     * @param  list<string>  $protectedDelegateIds
     *
     * @throws ValidationException When the seat would exceed its capacity after approval.
     */
    public function approveAndTerminateCurrentDelegates(Delegate $delegate, array $protectedDelegateIds = []): void
    {
        $this->approve(
            $delegate,
            $this->currentDelegatesOfSeat($delegate)->whereNotIn('id', $protectedDelegateIds)->modelKeys(),
        );
    }

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

        return $this->violation($delegates, $startedAt, $this->seatCapacityForSeat($seatId), []);
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
     * Ends the selected delegates of the seat so a delegate fits over the given period.
     *
     * A terminated delegate ends on the new delegate's start date, or on its own start date when that is later,
     * so an end date is never before the matching start date.
     *
     * @param  list<string>  $terminatedDelegateIds
     *
     * @throws ValidationException When the seat still exceeds its capacity once the selected delegates are ended.
     */
    private function terminateForCapacity(string $seatId, Carbon $startedAt, ?Carbon $endedAt, ?string $exceptDelegateId, array $terminatedDelegateIds): void
    {
        Seat::query()->whereKey($seatId)->lockForUpdate()->first();

        $candidates = $this->currentDelegatesOfSeatId($seatId, $exceptDelegateId, $startedAt, $endedAt);
        $capacity = $this->seatCapacityForSeat($seatId);
        $terminatedDelegates = $candidates->whereIn('id', $terminatedDelegateIds);
        $minimumTerminations = $this->violation($candidates, $startedAt, $capacity, []);

        if ($this->violation($candidates, $startedAt, $capacity, $terminatedDelegates->modelKeys()) > 0) {
            throw ValidationException::withMessages([
                'terminated_delegate_ids' => trans_choice('delegate.approval.capacity_exceeded', $minimumTerminations, ['min' => $minimumTerminations]),
            ]);
        }

        $terminatedDelegates->each(fn (Delegate $current): bool => $current->end(
            $current->started_at->gt($startedAt) ? $current->started_at->copy() : $startedAt->copy()
        ));
    }

    /**
     * Number of delegates over capacity at the busiest moment from the start date on once the new delegate is added.
     *
     * @param  Collection<int, Delegate>  $delegates  Approved delegates overlapping the period.
     * @param  list<string>  $excludedDelegateIds  Delegates ignored because they will be ended.
     */
    private function violation(Collection $delegates, Carbon $startedAt, int $capacity, array $excludedDelegateIds): int
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

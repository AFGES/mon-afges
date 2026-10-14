<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Models\Delegate;
use App\Models\Seat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DelegateApprovalService
{
    /**
     * Approve the delegate, ending the given currently active delegates of the same seat first.
     *
     * Keeping other delegates is only allowed while the seat capacity set for the college permits it.
     *
     * @param  list<string>  $terminatedDelegateIds
     *
     * @throws ValidationException When the seat would exceed its capacity after approval.
     */
    public function approve(Delegate $delegate, array $terminatedDelegateIds = []): void
    {
        DB::transaction(function () use ($delegate, $terminatedDelegateIds): void {
            $endedAt = $delegate->started_at ?? now();

            $terminatedDelegates = $this->currentDelegatesOfSeat($delegate)->whereIn('id', $terminatedDelegateIds);

            if ($terminatedDelegates->count() < $this->minimumTerminations($delegate)) {
                throw ValidationException::withMessages([
                    'terminated_delegate_ids' => trans_choice('delegate.approval.capacity_exceeded', $this->minimumTerminations($delegate), ['min' => $this->minimumTerminations($delegate)]),
                ]);
            }

            $terminatedDelegates->each(fn (Delegate $current): bool => $current->end($endedAt));

            $delegate->approve();
        });
    }

    /**
     * Create a delegate that is already approved by the authenticated user, ending the given current delegates of its seat first.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $terminatedDelegateIds
     *
     * @throws ValidationException When the seat would exceed its capacity after creation.
     */
    public function createApproved(array $attributes, array $terminatedDelegateIds = []): Delegate
    {
        return DB::transaction(function () use ($attributes, $terminatedDelegateIds): Delegate {
            $seatId = (string) $attributes['seat_id'];
            $endedAt = isset($attributes['started_at']) ? Carbon::parse($attributes['started_at']) : now();

            $terminatedDelegates = $this->currentDelegatesOfSeatId($seatId)->whereIn('id', $terminatedDelegateIds);
            $minimumTerminations = $this->minimumTerminationsForSeat($seatId);

            if ($terminatedDelegates->count() < $minimumTerminations) {
                throw ValidationException::withMessages([
                    'terminated_delegate_ids' => trans_choice('delegate.approval.capacity_exceeded', $minimumTerminations, ['min' => $minimumTerminations]),
                ]);
            }

            $terminatedDelegates->each(fn (Delegate $current): bool => $current->end($endedAt));

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
     * Approve the delegate, ending every other currently active delegate of its seat.
     */
    public function approveAndTerminateCurrentDelegates(Delegate $delegate): void
    {
        $this->approve($delegate, $this->currentDelegatesOfSeat($delegate)->modelKeys());
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
     * How many current delegates of the seat must be ended so the delegate fits within the seat capacity.
     */
    public function minimumTerminations(Delegate $delegate): int
    {
        return $this->minimumTerminationsForSeat((string) $delegate->seat_id, $delegate->getKey());
    }

    /**
     * How many current delegates of the seat must be ended so one more delegate fits within the seat capacity.
     */
    public function minimumTerminationsForSeat(string $seatId, ?string $exceptDelegateId = null): int
    {
        return max(0, $this->currentDelegatesOfSeatId($seatId, $exceptDelegateId)->count() + 1 - $this->seatCapacityForSeat($seatId));
    }

    /**
     * Approved, not ended delegates occupying the seat of the given delegate, excluding the delegate itself.
     *
     * @return Collection<int, Delegate>
     */
    public function currentDelegatesOfSeat(Delegate $delegate): Collection
    {
        return $this->currentDelegatesOfSeatId((string) $delegate->seat_id, $delegate->getKey());
    }

    /**
     * Approved, not ended delegates occupying the seat, oldest first, optionally excluding one delegate.
     *
     * @return Collection<int, Delegate>
     */
    public function currentDelegatesOfSeatId(string $seatId, ?string $exceptDelegateId = null): Collection
    {
        return Delegate::query()
            ->with('user')
            ->where('seat_id', $seatId)
            ->when($exceptDelegateId !== null, fn (Builder $query): Builder => $query->whereKeyNot($exceptDelegateId))
            ->oldest('started_at')
            ->get();
    }
}

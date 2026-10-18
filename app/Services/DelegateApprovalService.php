<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Models\Delegate;
use App\Models\Seat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DelegateApprovalService
{
    public function __construct(private SeatCapacityCalculator $seatCapacity) {}

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
    public function approve(Delegate $delegate, int|string|null $approvedBy, array $terminatedDelegateIds = []): void
    {
        DB::transaction(function () use ($delegate, $approvedBy, $terminatedDelegateIds): void {
            $this->terminateForCapacity(
                (string) $delegate->seat_id,
                $delegate->started_at ?? now(),
                $delegate->ended_at,
                $delegate->getKey(),
                $terminatedDelegateIds,
            );

            $delegate->approve($approvedBy);
        });
    }

    /**
     * Create a delegate that is already approved by the given user, ending the given approved delegates of its seat first.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $terminatedDelegateIds
     *
     * @throws ValidationException When the seat would exceed its capacity after creation.
     */
    public function createApproved(array $attributes, int|string|null $approvedBy, array $terminatedDelegateIds = []): Delegate
    {
        return DB::transaction(function () use ($attributes, $approvedBy, $terminatedDelegateIds): Delegate {
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
                'approved_by' => $approvedBy,
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
    public function approveAndTerminateCurrentDelegates(Delegate $delegate, int|string|null $approvedBy, array $protectedDelegateIds = []): void
    {
        $this->approve(
            $delegate,
            $approvedBy,
            $this->seatCapacity->currentDelegatesOfSeat($delegate)->whereNotIn('id', $protectedDelegateIds)->modelKeys(),
        );
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

        $candidates = $this->seatCapacity->currentDelegatesOfSeatId($seatId, $exceptDelegateId, $startedAt, $endedAt);
        $capacity = $this->seatCapacity->seatCapacityForSeat($seatId);
        $terminatedDelegates = $candidates->whereIn('id', $terminatedDelegateIds);
        $minimumTerminations = $this->seatCapacity->peakViolation($candidates, $startedAt, $capacity, []);

        if ($this->seatCapacity->peakViolation($candidates, $startedAt, $capacity, $terminatedDelegates->modelKeys()) > 0) {
            throw ValidationException::withMessages([
                'terminated_delegate_ids' => trans_choice('delegate.approval.capacity_exceeded', $minimumTerminations, ['min' => $minimumTerminations]),
            ]);
        }

        $terminatedDelegates->each(fn (Delegate $current): bool => $current->end(
            $current->started_at->gt($startedAt) ? $current->started_at->copy() : $startedAt->copy()
        ));
    }
}

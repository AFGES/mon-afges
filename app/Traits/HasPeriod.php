<?php

declare(strict_types=1);

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

trait HasPeriod
{
    /**
     * Scope a query to only include currently active records.
     *
     * A period is half-open: [started_at, ended_at). It is active when it has
     * started and its end is either unset or strictly after now.
     *
     * @param  Builder<static>  $query
     */
    public function scopeCurrentlyActive(Builder $query): void
    {
        $now = now();

        $query->where('started_at', '<=', $now)
            ->where(function (Builder $query) use ($now): void {
                $query->whereNull('ended_at')
                    ->orWhere('ended_at', '>', $now);
            });
    }

    /**
     * Scope a query to only include records that have ended (ended_at <= now).
     *
     * @param  Builder<static>  $query
     */
    public function scopeEnded(Builder $query): void
    {
        $query->whereNotNull('ended_at')
            ->where('ended_at', '<=', now());
    }

    /**
     * Scope a query to only include records that have not started yet.
     *
     * @param  Builder<static>  $query
     */
    public function scopeNotStarted(Builder $query): void
    {
        $query->where('started_at', '>', now());
    }

    /**
     * Scope a query to only include records that started at or before an instant.
     *
     * @param  Builder<static>  $query
     */
    public function scopeStartedBy(Builder $query, Carbon|string $date): void
    {
        $query->where('started_at', '<=', $date);
    }

    /**
     * Scope a query to only include records that end strictly after an instant
     * (the end instant itself is excluded, as periods are half-open).
     *
     * @param  Builder<static>  $query
     */
    public function scopeEndedAfter(Builder $query, Carbon|string $date): void
    {
        $query->whereNotNull('ended_at')
            ->where('ended_at', '>', $date);
    }

    /**
     * Whether the period is active now: started_at <= now < ended_at (open end allowed).
     */
    public function isCurrentlyActive(): bool
    {
        $now = now();

        $started = $this->started_at instanceof Carbon ? $this->started_at : Carbon::parse($this->started_at);

        if ($started->isAfter($now)) {
            return false;
        }

        if ($this->ended_at === null) {
            return true;
        }

        $ended = $this->ended_at instanceof Carbon ? $this->ended_at : Carbon::parse($this->ended_at);

        return $ended->isAfter($now);
    }

    /**
     * Whether the period has ended: ended_at <= now.
     */
    public function hasEnded(): bool
    {
        if ($this->ended_at === null) {
            return false;
        }

        $ended = $this->ended_at instanceof Carbon ? $this->ended_at : Carbon::parse($this->ended_at);

        return $ended->lte(now());
    }

    public function end(?Carbon $date = null): bool
    {
        if ($this->hasEnded()) {
            return false;
        }

        $this->update([
            'ended_at' => $date ?? now(),
        ]);

        return true;
    }

    public function initializeHasPeriod(): void
    {
        $this->fillable = array_merge($this->fillable, [
            'started_at',
            'ended_at',
        ]);

        $this->mergeCasts(array_diff_key([
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ], $this->getCasts()));
    }
}

<?php

declare(strict_types=1);

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

trait HasPeriod
{
    /**
     * Scope a query to only include currently active records.
     */
    public function scopeCurrentlyActive(Builder $query): void
    {
        $query->whereTodayOrBefore('started_at')
            ->where(function (Builder $query) {
                $query->whereNull('ended_at')
                    ->orWhereTodayOrAfter('ended_at');
            });
    }

    /**
     * Scope a query to only include records that have ended.
     */
    public function scopeEnded(Builder $query): void
    {
        $query->whereNotNull('ended_at')
            ->where('ended_at', '<', now()->startOfDay());
    }

    /**
     * Scope a query to only include records that have not started yet.
     */
    public function scopeNotStarted(Builder $query): void
    {
        $query->where('started_at', '>', now());
    }

    /**
     * Scope a query to only include records that started on or before a given date.
     */
    public function scopeStartedBy(Builder $query, Carbon|string $date): void
    {
        $query->where('started_at', '<=', $date);
    }

    /**
     * Scope a query to only include records that ended on or after a given date.
     */
    public function scopeEndedAfter(Builder $query, Carbon|string $date): void
    {
        $query->whereNotNull('ended_at')
            ->where('ended_at', '>=', $date);
    }

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

        return $ended->isAfter($now) || $ended->isSameDay($now);
    }

    public function hasEnded(): bool
    {
        if ($this->ended_at === null) {
            return false;
        }

        $ended = $this->ended_at instanceof Carbon ? $this->ended_at : Carbon::parse($this->ended_at);

        return $ended->isBefore(now()) && ! $ended->isSameDay(now());
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

        $this->casts = array_merge($this->casts ?? [], [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ]);
    }
}

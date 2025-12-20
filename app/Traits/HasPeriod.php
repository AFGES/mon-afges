<?php

declare(strict_types=1);

namespace App\Traits;

use Carbon\Carbon;

trait HasPeriod
{
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

    public function terminate(): bool
    {
        return $this->end();
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

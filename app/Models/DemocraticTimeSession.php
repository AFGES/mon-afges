<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasPeriod;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DemocraticTimeSession extends Model
{
    /** @use HasFactory<\Database\Factories\DemocraticTimeSessionFactory> */
    use HasFactory, HasPeriod, HasUuids;

    public $fillable = [
        'democratic_time_type_id',
        'location',
        'sign_in_opens_at',
        'sign_in_closes_at',
        'auto_open_sign_in',
        'minutes_before_start',
    ];

    protected function casts(): array
    {
        return [
            'sign_in_opens_at' => 'datetime',
            'sign_in_closes_at' => 'datetime',
            'auto_open_sign_in' => 'boolean',
            'minutes_before_start' => 'integer',
        ];
    }

    public function democraticTimeType(): BelongsTo
    {
        return $this->belongsTo(DemocraticTimeType::class);
    }

    public function proxies(): HasMany
    {
        return $this->hasMany(Proxy::class);
    }

    public function signIns(): HasMany
    {
        return $this->hasMany(SessionSignIn::class);
    }

    /**
     * Check if sign-in is currently open.
     */
    public function isSignInOpen(): bool
    {
        if ($this->sign_in_opens_at === null) {
            return false;
        }

        $now = now();

        $isAfterOpen = $this->sign_in_opens_at->isBefore($now) || $this->sign_in_opens_at->isSameMinute($now);

        if ($this->sign_in_closes_at === null) {
            return $isAfterOpen;
        }

        $isBeforeClose = $this->sign_in_closes_at->isAfter($now) || $this->sign_in_closes_at->isSameMinute($now);

        return $isAfterOpen && $isBeforeClose;
    }

    /**
     * Manually open sign-in.
     */
    public function openSignIn(): bool
    {
        if ($this->sign_in_opens_at !== null) {
            return false; // Already opened
        }

        $this->update([
            'sign_in_opens_at' => now(),
        ]);

        return true;
    }

    /**
     * Manually close sign-in.
     * If session has ended, sets signed_out_at to ended_at for delegates still signed in.
     */
    public function closeSignIn(): bool
    {
        if ($this->sign_in_closes_at !== null) {
            return false; // Already closed
        }

        $closeTime = $this->ended_at ?? now();

        // Update sign-out time for all delegates still signed in
        $this->signIns()->whereNull('signed_out_at')->update([
            'signed_out_at' => $closeTime,
        ]);

        $this->update([
            'sign_in_closes_at' => $closeTime,
        ]);

        return true;
    }

    /**
     * Auto-open sign-in based on minutes_before_start.
     */
    public function autoOpenSignIn(): bool
    {
        if (! $this->auto_open_sign_in || $this->sign_in_opens_at !== null || ! $this->started_at) {
            return false;
        }

        $opensAt = $this->started_at->copy()->subMinutes($this->minutes_before_start);

        if ($opensAt->isFuture()) {
            return false;
        }

        $this->update([
            'sign_in_opens_at' => $opensAt,
        ]);

        return true;
    }

    /**
     * Auto-close sign-in when session ends.
     * Also sets signed_out_at to ended_at for delegates still signed in.
     */
    public function autoCloseSignIn(): bool
    {
        if ($this->sign_in_closes_at !== null || ! $this->ended_at) {
            return false;
        }

        // Update sign-out time for all delegates still signed in
        $this->signIns()->whereNull('signed_out_at')->update([
            'signed_out_at' => $this->ended_at,
        ]);

        $this->update([
            'sign_in_closes_at' => $this->ended_at,
        ]);

        return true;
    }
}

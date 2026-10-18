<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SessionStatus;
use App\Enums\SignInStatus;
use App\Traits\HasPeriod;
use Carbon\Carbon;
use Database\Factories\DemocraticTimeSessionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $ended_at
 */
final class DemocraticTimeSession extends Model
{
    /** @use HasFactory<DemocraticTimeSessionFactory> */
    use HasFactory;

    use HasPeriod;
    use HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'democratic_time_type_id',
        'location',
        'sign_in_opens_at',
        'sign_in_closes_at',
        'auto_open_sign_in',
        'minutes_before_start',
        'calculate_quorum_per_college',
        'quorum_percentage',
        'include_proxies_in_quorum',
    ];

    /** @return BelongsTo<DemocraticTimeType, $this> */
    public function democraticTimeType(): BelongsTo
    {
        return $this->belongsTo(DemocraticTimeType::class);
    }

    /** @return HasMany<Proxy, $this> */
    public function proxies(): HasMany
    {
        return $this->hasMany(Proxy::class);
    }

    /** @return HasMany<SessionSignIn, $this> */
    public function signIns(): HasMany
    {
        return $this->hasMany(SessionSignIn::class);
    }

    /**
     * Current sign-in status derived from the sign-in window.
     */
    public function signInStatus(): SignInStatus
    {
        if ($this->isSignInOpen()) {
            return SignInStatus::Open;
        }

        if ($this->sign_in_closes_at !== null) {
            return SignInStatus::Closed;
        }

        return SignInStatus::NotOpened;
    }

    /**
     * Current session status derived from its period.
     */
    public function sessionStatus(): SessionStatus
    {
        if ($this->isCurrentlyActive()) {
            return SessionStatus::Ongoing;
        }

        if ($this->hasEnded()) {
            return SessionStatus::Ended;
        }

        return SessionStatus::Upcoming;
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
            return false;
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
            return false;
        }

        $this->closeSignInAt($this->ended_at ?? now());

        return true;
    }

    /**
     * Auto-open sign-in based on minutes_before_start.
     */
    public function autoOpenSignIn(): bool
    {
        if (! $this->auto_open_sign_in) {
            return false;
        }

        if ($this->sign_in_opens_at !== null) {
            return false;
        }

        if (! $this->started_at) {
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
        if ($this->sign_in_closes_at !== null) {
            return false;
        }

        if (! $this->ended_at) {
            return false;
        }

        $this->closeSignInAt($this->ended_at);

        return true;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'sign_in_opens_at' => 'datetime',
            'sign_in_closes_at' => 'datetime',
            'auto_open_sign_in' => 'boolean',
            'minutes_before_start' => 'integer',
            'calculate_quorum_per_college' => 'boolean',
            'quorum_percentage' => 'decimal:2',
            'include_proxies_in_quorum' => 'boolean',
        ];
    }

    /**
     * Sign out every delegate still signed in and close sign-in at the given time.
     */
    private function closeSignInAt(Carbon $closeTime): void
    {
        $this->signIns()->whereNull('signed_out_at')->update([
            'signed_out_at' => $closeTime,
        ]);

        $this->update([
            'sign_in_closes_at' => $closeTime,
        ]);
    }
}

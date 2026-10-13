<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasPeriod;
use Database\Factories\DemocraticTimeSessionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * Get total number of seats eligible for quorum (by college if specified).
     */
    public function getTotalEligibleSeats(?string $collegeId = null): int
    {
        $query = Seat::query()
            ->whereHas('collegeOrganization.college', function (Builder $q): void {
                $q->where('can_vote', true);
            });

        if ($collegeId) {
            $query->whereHas('collegeOrganization', function (Builder $q) use ($collegeId): void {
                $q->where('college_id', $collegeId);
            });
        }

        return $query->count();
    }

    /**
     * Get number of present seats (seats with at least one signed-in delegate).
     */
    public function getPresentSeatsCount(?string $collegeId = null, bool $includeProxies = true): int
    {
        // Count distinct seats with signed-in delegates
        $query = $this->signIns()
            ->whereNull('signed_out_at')
            ->select('seat_id')
            ->distinct();

        if ($collegeId) {
            $query->whereHas('seat.collegeOrganization', function (Builder $q) use ($collegeId): void {
                $q->where('college_id', $collegeId);
            });
        }

        $seats = $query->get();

        if (! $includeProxies) {
            return $seats->count();
        }

        // Add seats with approved proxies (if they're not already counted)
        $proxyQuery = $this->proxies()
            ->whereNotIn('delegator_seat_id', $seats->pluck('seat_id'))
            ->distinct();

        if ($collegeId) {
            $proxyQuery->whereHas('delegatorSeat.collegeOrganization', function (Builder $q) use ($collegeId): void {
                $q->where('college_id', $collegeId);
            });
        }

        return $seats->count() + $proxyQuery->count();
    }

    /**
     * Check if quorum is met for the session.
     */
    public function hasQuorum(?string $collegeId = null): bool
    {
        // Use session-level quorum settings if available, otherwise fall back to type
        $calculatePerCollege = $this->calculate_quorum_per_college ?? $this->democraticTimeType?->calculate_quorum_per_college ?? false;
        $quorumPercentage = $this->quorum_percentage ?? $this->democraticTimeType?->quorum_percentage ?? 50;
        $includeProxies = $this->include_proxies_in_quorum ?? $this->democraticTimeType?->include_proxies_in_quorum ?? true;

        $totalEligible = $this->getTotalEligibleSeats($collegeId);

        if ($totalEligible === 0) {
            return false;
        }

        $presentCount = $this->getPresentSeatsCount($collegeId, $includeProxies);
        $attendancePercentage = ($presentCount / $totalEligible) * 100;

        return $attendancePercentage >= $quorumPercentage;
    }

    /**
     * Get quorum status for all voting colleges.
     *
     * @return array{met: bool, type: string, total_eligible?: int, present_count?: int, percentage?: float|int, required_percentage?: mixed, details?: list<array{college_id: string, college_name: string, total_eligible: int, present_count: int, percentage: float|int, required_percentage: mixed, met: bool}>}
     */
    public function getQuorumStatus(): array
    {
        // Use session-level quorum settings if available, otherwise fall back to type
        $calculatePerCollege = $this->calculate_quorum_per_college ?? $this->democraticTimeType?->calculate_quorum_per_college ?? false;
        $quorumPercentage = $this->quorum_percentage ?? $this->democraticTimeType?->quorum_percentage ?? 50;
        $includeProxies = $this->include_proxies_in_quorum ?? $this->democraticTimeType?->include_proxies_in_quorum ?? true;

        if (! $calculatePerCollege) {
            // All colleges combined
            $totalEligible = $this->getTotalEligibleSeats();
            $presentCount = $this->getPresentSeatsCount(null, $includeProxies);
            $percentage = $totalEligible > 0 ? ($presentCount / $totalEligible) * 100 : 0;

            return [
                'met' => $this->hasQuorum(),
                'type' => 'combined',
                'total_eligible' => $totalEligible,
                'present_count' => $presentCount,
                'percentage' => round($percentage, 2),
                'required_percentage' => $quorumPercentage,
            ];
        }

        // Per college calculation
        $votingColleges = College::where('can_vote', true)->get();
        $details = [];
        $allMet = true;

        foreach ($votingColleges as $college) {
            $totalEligible = $this->getTotalEligibleSeats($college->id);
            $presentCount = $this->getPresentSeatsCount($college->id, $includeProxies);
            $percentage = $totalEligible > 0 ? ($presentCount / $totalEligible) * 100 : 0;
            $met = $this->hasQuorum($college->id);

            $details[] = [
                'college_id' => $college->id,
                'college_name' => $college->name,
                'total_eligible' => $totalEligible,
                'present_count' => $presentCount,
                'percentage' => round($percentage, 2),
                'required_percentage' => $quorumPercentage,
                'met' => $met,
            ];

            if (! $met && $totalEligible > 0) {
                $allMet = false;
            }
        }

        return [
            'met' => $allMet,
            'type' => 'per_college',
            'details' => $details,
        ];
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
}

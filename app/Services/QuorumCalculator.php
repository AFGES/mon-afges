<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\QuorumMode;
use App\Models\College;
use App\Models\DemocraticTimeSession;
use App\Models\Seat;
use Illuminate\Database\Eloquent\Builder;

final class QuorumCalculator
{
    /**
     * Get total number of seats eligible for quorum (by college if specified).
     */
    public function totalEligibleSeats(?string $collegeId = null): int
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
    public function presentSeatsCount(DemocraticTimeSession $session, ?string $collegeId = null, bool $includeProxies = true): int
    {
        $query = $session->signIns()
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
        $proxyQuery = $session->proxies()
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
    public function hasQuorum(DemocraticTimeSession $session, ?string $collegeId = null): bool
    {
        $settings = $this->resolveSettings($session);

        return $this->collegeFigures($session, $collegeId, $settings['quorumPercentage'], $settings['includeProxies'])['met'];
    }

    /**
     * Get quorum status for all voting colleges.
     *
     * @return array{met: bool, type: string, total_eligible?: int, present_count?: int, percentage?: float|int, required_percentage?: mixed, details?: list<array{college_id: string, college_name: string, total_eligible: int, present_count: int, percentage: float|int, required_percentage: mixed, met: bool}>}
     */
    public function status(DemocraticTimeSession $session): array
    {
        $settings = $this->resolveSettings($session);
        $quorumPercentage = $settings['quorumPercentage'];

        if ($settings['mode'] === QuorumMode::Combined) {
            $figures = $this->collegeFigures($session, null, $quorumPercentage, $settings['includeProxies']);

            return [
                'met' => $figures['met'],
                'type' => QuorumMode::Combined->value,
                'total_eligible' => $figures['total_eligible'],
                'present_count' => $figures['present_count'],
                'percentage' => $figures['percentage'],
                'required_percentage' => $quorumPercentage,
            ];
        }

        $details = [];
        $allMet = true;

        foreach (College::where('can_vote', true)->get() as $college) {
            $figures = $this->collegeFigures($session, $college->id, $quorumPercentage, $settings['includeProxies']);

            $details[] = [
                'college_id' => $college->id,
                'college_name' => $college->name,
                'total_eligible' => $figures['total_eligible'],
                'present_count' => $figures['present_count'],
                'percentage' => $figures['percentage'],
                'required_percentage' => $quorumPercentage,
                'met' => $figures['met'],
            ];

            if (! $figures['met'] && $figures['total_eligible'] > 0) {
                $allMet = false;
            }
        }

        return [
            'met' => $allMet,
            'type' => QuorumMode::PerCollege->value,
            'details' => $details,
        ];
    }

    /**
     * Counts are computed once; `met` is derived from them.
     *
     * @return array{total_eligible: int, present_count: int, percentage: float|int, met: bool}
     */
    private function collegeFigures(DemocraticTimeSession $session, ?string $collegeId, mixed $quorumPercentage, mixed $includeProxies): array
    {
        $totalEligible = $this->totalEligibleSeats($collegeId);
        $presentCount = $this->presentSeatsCount($session, $collegeId, (bool) $includeProxies);
        $rawPercentage = $totalEligible > 0 ? ($presentCount / $totalEligible) * 100 : 0;

        return [
            'total_eligible' => $totalEligible,
            'present_count' => $presentCount,
            'percentage' => round($rawPercentage, 2),
            'met' => $totalEligible > 0 && $rawPercentage >= $quorumPercentage,
        ];
    }

    /**
     * Session-level quorum settings, falling back to the democratic time type.
     *
     * @return array{mode: QuorumMode, quorumPercentage: mixed, includeProxies: mixed}
     */
    private function resolveSettings(DemocraticTimeSession $session): array
    {
        $calculatePerCollege = $session->calculate_quorum_per_college ?? $session->democraticTimeType->calculate_quorum_per_college ?? false;

        return [
            'mode' => $calculatePerCollege ? QuorumMode::PerCollege : QuorumMode::Combined,
            'quorumPercentage' => $session->quorum_percentage ?? $session->democraticTimeType->quorum_percentage ?? 50,
            'includeProxies' => $session->include_proxies_in_quorum ?? $session->democraticTimeType->include_proxies_in_quorum ?? true,
        ];
    }
}

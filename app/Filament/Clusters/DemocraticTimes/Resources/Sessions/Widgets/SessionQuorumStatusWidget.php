<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Sessions\Widgets;

use App\Models\DemocraticTimeSession;
use App\Services\QuorumCalculator;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

final class SessionQuorumStatusWidget extends ChartWidget
{
    public ?DemocraticTimeSession $record = null;

    /** @var array<string, mixed>|null */
    private ?array $quorumStatus = null;

    public function getHeading(): string|Htmlable|null
    {
        if (! $this->record) {
            return null;
        }

        $status = $this->quorumStatus($this->record);

        if ($status['met']) {
            $label = __('emargement.quorum.met');

            return "{$label} - {$status['percentage']}%";
        }

        $label = __('emargement.quorum.not_met');

        return "{$label} - {$status['percentage']}%";
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        if (! $this->record) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $status = $this->quorumStatus($this->record);
        $presentCount = $status['present_count'];
        $absentCount = $status['total_eligible'] - $status['present_count'];
        $presentLabel = __('emargement.present');
        $absentLabel = __('emargement.absent');

        return [
            'datasets' => [
                [
                    'data' => [$presentCount, $absentCount],
                    'backgroundColor' => [
                        'rgb(34, 197, 94)', // green-500 for present
                        'rgb(239, 68, 68)', // red-500 for absent
                    ],
                ],
            ],
            'labels' => [
                "{$presentLabel} ({$presentCount})",
                "{$absentLabel} ({$absentCount})",
            ],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    /**
     * Quorum status of the record, computed once per request.
     *
     * @return array{met: bool, type: string, total_eligible?: int, present_count?: int, percentage?: float|int, required_percentage?: mixed, details?: list<array<string, mixed>>}
     */
    private function quorumStatus(DemocraticTimeSession $record): array
    {
        return $this->quorumStatus ??= resolve(QuorumCalculator::class)->status($record);
    }
}

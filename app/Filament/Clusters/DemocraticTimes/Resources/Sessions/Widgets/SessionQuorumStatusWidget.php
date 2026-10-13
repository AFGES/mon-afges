<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Sessions\Widgets;

use App\Models\DemocraticTimeSession;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

final class SessionQuorumStatusWidget extends ChartWidget
{
    public ?DemocraticTimeSession $record = null;

    public function getHeading(): string|Htmlable|null
    {
        if (! $this->record) {
            return null;
        }

        $status = $this->record->getQuorumStatus();

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

        $status = $this->record->getQuorumStatus();
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
}

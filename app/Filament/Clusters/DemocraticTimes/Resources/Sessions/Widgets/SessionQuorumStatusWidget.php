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
            return __('emargement.quorum.met').' - '.$status['percentage'].'%';
        }

        return __('emargement.quorum.not_met').' - '.$status['percentage'].'%';
    }

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
                __('emargement.present').' ('.$presentCount.')',
                __('emargement.absent').' ('.$absentCount.')',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}

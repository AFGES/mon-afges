<?php

declare(strict_types=1);

namespace App\Filament\Resources\Volunteers\Widgets;

use App\Enums\VolunteerStatus;
use App\Models\Volunteer;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class PendingVolunteerStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $pendingCount = Volunteer::withoutGlobalScopes()
            ->where('status', VolunteerStatus::Pending)
            ->count();

        return [
            Stat::make(__('volunteer.stats.pending_approvals'), $pendingCount)
                ->description(__('volunteer.stats.pending_description'))
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color('warning')
                ->url(route('filament.root.resources.volunteers.index', [
                    'tableFilters' => [
                        'status' => [
                            'value' => VolunteerStatus::Pending->value,
                        ],
                    ],
                ])),
        ];
    }
}

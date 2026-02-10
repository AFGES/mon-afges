<?php

declare(strict_types=1);

namespace App\Filament\Resources\Volunteers\Widgets;

use App\Enums\ApprovalStatus;
use App\Models\Volunteer;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class PendingVolunteerStats extends StatsOverviewWidget
{
    use HasWidgetShield;

    protected function getStats(): array
    {
        $pendingCount = Volunteer::withoutGlobalScopes()
            ->pending()
            ->count();

        return [
            Stat::make(__('volunteer.stats.pending_approvals'), $pendingCount)
                ->description(__('volunteer.stats.pending_description'))
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color('warning')
                ->url(route('filament.root.resources.volunteers.index', [
                    'tableFilters' => [
                        'approval_status' => [
                            'value' => ApprovalStatus::Pending->value,
                        ],
                    ],
                ])),
        ];
    }
}

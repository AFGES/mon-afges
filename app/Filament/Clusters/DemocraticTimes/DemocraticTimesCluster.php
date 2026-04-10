<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

final class DemocraticTimesCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    public static function getNavigationLabel(): string
    {
        return __('navigation.clusters.democratic_times');
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Settings\Resources\DemocraticTimeTypes\Pages;

use App\Filament\Clusters\Settings\Resources\DemocraticTimeTypes\DemocraticTimeTypeResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateDemocraticTimeType extends CreateRecord
{
    protected static string $resource = DemocraticTimeTypeResource::class;
}

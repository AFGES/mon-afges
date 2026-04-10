<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Sessions\Pages;

use App\Filament\Clusters\DemocraticTimes\Resources\Sessions\SessionResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateSession extends CreateRecord
{
    protected static string $resource = SessionResource::class;
}

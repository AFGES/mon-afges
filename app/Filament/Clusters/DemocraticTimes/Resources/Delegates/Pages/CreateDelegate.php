<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Pages;

use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\DelegateResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateDelegate extends CreateRecord
{
    protected static string $resource = DelegateResource::class;
}

<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Proxies\Pages;

use App\Filament\Clusters\DemocraticTimes\Resources\Proxies\ProxyResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateProxy extends CreateRecord
{
    protected static string $resource = ProxyResource::class;
}

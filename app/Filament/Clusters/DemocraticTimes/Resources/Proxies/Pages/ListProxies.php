<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Proxies\Pages;

use App\Filament\Clusters\DemocraticTimes\Resources\Proxies\ProxyResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListProxies extends ListRecords
{
    protected static string $resource = ProxyResource::class;

    /**
     * @return array<Action|ActionGroup>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

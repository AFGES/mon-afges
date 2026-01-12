<?php

namespace App\Filament\Resources\Proxys\Pages;

use App\Filament\Resources\Proxys\ProxyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProxys extends ListRecords
{
    protected static string $resource = ProxyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

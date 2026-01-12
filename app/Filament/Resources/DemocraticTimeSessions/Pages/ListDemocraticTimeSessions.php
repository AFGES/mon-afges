<?php

namespace App\Filament\Resources\DemocraticTimeSessions\Pages;

use App\Filament\Resources\DemocraticTimeSessions\DemocraticTimeSessionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDemocraticTimeSessions extends ListRecords
{
    protected static string $resource = DemocraticTimeSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

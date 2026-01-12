<?php

namespace App\Filament\Resources\DemocraticTimeTypes\Pages;

use App\Filament\Resources\DemocraticTimeTypes\DemocraticTimeTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDemocraticTimeTypes extends ListRecords
{
    protected static string $resource = DemocraticTimeTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

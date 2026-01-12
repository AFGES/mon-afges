<?php

namespace App\Filament\Resources\DemocraticTimeTypes\Pages;

use App\Filament\Resources\DemocraticTimeTypes\DemocraticTimeTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDemocraticTimeType extends EditRecord
{
    protected static string $resource = DemocraticTimeTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

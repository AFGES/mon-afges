<?php

namespace App\Filament\Resources\DemocraticTimeSessions\Pages;

use App\Filament\Resources\DemocraticTimeSessions\DemocraticTimeSessionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDemocraticTimeSession extends EditRecord
{
    protected static string $resource = DemocraticTimeSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

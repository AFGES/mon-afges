<?php

namespace App\Filament\Clusters\DemocraticTimes\Resources\Sessions\Pages;

use App\Filament\Clusters\DemocraticTimes\Resources\Sessions\SessionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSession extends EditRecord
{
    protected static string $resource = SessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\Volunteers\Pages;

use App\Filament\Resources\Volunteers\VolunteerResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewVolunteer extends ViewRecord
{
    protected static string $resource = VolunteerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

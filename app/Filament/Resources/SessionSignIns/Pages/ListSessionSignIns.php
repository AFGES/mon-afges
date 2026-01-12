<?php

namespace App\Filament\Resources\SessionSignIns\Pages;

use App\Filament\Resources\SessionSignIns\SessionSignInResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSessionSignIns extends ListRecords
{
    protected static string $resource = SessionSignInResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\Proxys\Pages;

use App\Filament\Resources\Proxys\ProxyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProxy extends EditRecord
{
    protected static string $resource = ProxyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

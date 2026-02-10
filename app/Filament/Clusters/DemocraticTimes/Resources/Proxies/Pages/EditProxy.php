<?php

namespace App\Filament\Clusters\DemocraticTimes\Resources\Proxies\Pages;

use App\Filament\Clusters\DemocraticTimes\Resources\Proxies\ProxyResource;
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

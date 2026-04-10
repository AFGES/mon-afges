<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Settings\Resources\DemocraticTimeTypes\Pages;

use App\Filament\Clusters\Settings\Resources\DemocraticTimeTypes\DemocraticTimeTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditDemocraticTimeType extends EditRecord
{
    protected static string $resource = DemocraticTimeTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

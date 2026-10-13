<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Settings\Resources\DemocraticTimeTypes\Pages;

use App\Filament\Clusters\Settings\Resources\DemocraticTimeTypes\DemocraticTimeTypeResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListDemocraticTimeTypes extends ListRecords
{
    protected static string $resource = DemocraticTimeTypeResource::class;

    /**
     * @return array<Action|ActionGroup>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

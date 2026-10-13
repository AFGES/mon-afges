<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Pages;

use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\DelegateResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListDelegates extends ListRecords
{
    protected static string $resource = DelegateResource::class;

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

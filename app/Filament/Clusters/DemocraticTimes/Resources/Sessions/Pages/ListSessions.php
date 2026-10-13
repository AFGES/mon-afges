<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Sessions\Pages;

use App\Filament\Clusters\DemocraticTimes\Resources\Sessions\SessionResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListSessions extends ListRecords
{
    protected static string $resource = SessionResource::class;

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

<?php

declare(strict_types=1);

namespace App\Filament\Resources\Volunteers\Pages;

use App\Filament\Resources\Volunteers\VolunteerResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListVolunteers extends ListRecords
{
    protected static string $resource = VolunteerResource::class;

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

<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\SessionSignIns\Pages;

use App\Filament\Clusters\DemocraticTimes\Resources\SessionSignIns\SessionSignInResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListSessionSignIns extends ListRecords
{
    protected static string $resource = SessionSignInResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getTableQuery(): ?Builder
    {
        return parent::getTableQuery()
            ->with([
                'seat.collegeOrganization.college',
                'seat.delegates.user',
                'signedInBySeat.collegeOrganization.college',
                'signedInBySeat.delegates.user',
            ]);
    }
}

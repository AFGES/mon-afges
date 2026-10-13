<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\SessionSignIns\Pages;

use App\Filament\Clusters\DemocraticTimes\Resources\SessionSignIns\SessionSignInResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

final class ListSessionSignIns extends ListRecords
{
    protected static string $resource = SessionSignInResource::class;

    /**
     * @return array<Action|ActionGroup>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder|Relation|null
    {
        return parent::getTableQuery()
            ?->with([
                'seat.collegeOrganization.college',
                'seat.delegates.user',
                'signedInBySeat.collegeOrganization.college',
                'signedInBySeat.delegates.user',
            ]);
    }
}

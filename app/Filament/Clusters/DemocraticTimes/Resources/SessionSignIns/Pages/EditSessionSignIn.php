<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\SessionSignIns\Pages;

use App\Filament\Clusters\DemocraticTimes\Resources\SessionSignIns\SessionSignInResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditSessionSignIn extends EditRecord
{
    protected static string $resource = SessionSignInResource::class;

    /**
     * @return array<Action|ActionGroup>
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

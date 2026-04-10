<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\SessionSignIns\Pages;

use App\Filament\Clusters\DemocraticTimes\Resources\SessionSignIns\SessionSignInResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditSessionSignIn extends EditRecord
{
    protected static string $resource = SessionSignInResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

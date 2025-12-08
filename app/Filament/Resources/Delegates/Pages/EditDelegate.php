<?php

declare(strict_types=1);

namespace App\Filament\Resources\Delegates\Pages;

use App\Filament\Resources\Delegates\DelegateResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

final class EditDelegate extends EditRecord
{
    protected static string $resource = DelegateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}

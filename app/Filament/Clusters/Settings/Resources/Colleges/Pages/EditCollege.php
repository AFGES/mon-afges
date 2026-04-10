<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Settings\Resources\Colleges\Pages;

use App\Filament\Clusters\Settings\Resources\Colleges\CollegeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditCollege extends EditRecord
{
    protected static string $resource = CollegeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

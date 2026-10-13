<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Settings\Resources\Colleges\Pages;

use App\Filament\Clusters\Settings\Resources\Colleges\CollegeResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditCollege extends EditRecord
{
    protected static string $resource = CollegeResource::class;

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

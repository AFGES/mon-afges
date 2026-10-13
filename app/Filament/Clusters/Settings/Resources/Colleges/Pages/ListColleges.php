<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Settings\Resources\Colleges\Pages;

use App\Filament\Clusters\Settings\Resources\Colleges\CollegeResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListColleges extends ListRecords
{
    protected static string $resource = CollegeResource::class;

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

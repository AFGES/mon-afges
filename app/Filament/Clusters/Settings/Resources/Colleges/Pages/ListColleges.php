<?php

namespace App\Filament\Clusters\Settings\Resources\Colleges\Pages;

use App\Filament\Clusters\Settings\Resources\Colleges\CollegeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListColleges extends ListRecords
{
    protected static string $resource = CollegeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

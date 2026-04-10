<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Settings\Resources\Colleges\Pages;

use App\Filament\Clusters\Settings\Resources\Colleges\CollegeResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCollege extends CreateRecord
{
    protected static string $resource = CollegeResource::class;
}

<?php

namespace App\Filament\Resources\Delegates\Pages;

use App\Filament\Resources\Delegates\DelegateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDelegate extends CreateRecord
{
    protected static string $resource = DelegateResource::class;
}

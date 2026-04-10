<?php

declare(strict_types=1);

namespace App\Filament\Resources\Volunteers\Pages;

use App\Filament\Resources\Volunteers\VolunteerResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateVolunteer extends CreateRecord
{
    protected static string $resource = VolunteerResource::class;
}

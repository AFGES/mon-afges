<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\SessionSignIns\Pages;

use App\Filament\Clusters\DemocraticTimes\Resources\SessionSignIns\SessionSignInResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateSessionSignIn extends CreateRecord
{
    protected static string $resource = SessionSignInResource::class;
}

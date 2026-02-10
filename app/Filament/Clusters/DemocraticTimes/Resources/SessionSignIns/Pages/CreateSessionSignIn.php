<?php

namespace App\Filament\Clusters\DemocraticTimes\Resources\SessionSignIns\Pages;

use App\Filament\Clusters\DemocraticTimes\Resources\SessionSignIns\SessionSignInResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSessionSignIn extends CreateRecord
{
    protected static string $resource = SessionSignInResource::class;
}

<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Organization;
use Jeffgreco13\FilamentBreezy\Pages\MyProfilePage;

final class MyProfile extends MyProfilePage
{
    public function getRegisteredMyProfileComponents(): array
    {
        $components = parent::getRegisteredMyProfileComponents();

        // Add our custom organization component
        $components['organizations'] = \App\Livewire\ManageOrganizations::class;

        return $components;
    }
}

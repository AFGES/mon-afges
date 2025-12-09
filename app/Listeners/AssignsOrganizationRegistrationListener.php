<?php

namespace App\Listeners;

use App\Models\Organization;
use App\Models\User;
use Filament\Auth\Events\Registered;

class AssignsOrganizationRegistrationListener
{
    public function __construct() {}

    public function handle(Registered $event): void
    {
        $user = $event->getUser();
        if ($user instanceof User) {
            $organization = Organization::where('domain', $user->email)->first();
        }
    }
}

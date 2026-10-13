<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use DutchCodingCompany\FilamentSocialite\Events\Login;
use DutchCodingCompany\FilamentSocialite\Events\Registered;
use DutchCodingCompany\FilamentSocialite\Events\SocialiteUserConnected;
use Illuminate\Contracts\Auth\Authenticatable;

final class MarkEmailAsVerifiedOnSocialiteLogin
{
    public function handleLogin(Login $event): void
    {
        $this->markEmailAsVerified($event->socialiteUser->getUser());
    }

    public function handleRegistered(Registered $event): void
    {
        $this->markEmailAsVerified($event->socialiteUser->getUser());
    }

    public function handleSocialiteUserConnected(SocialiteUserConnected $event): void
    {
        $this->markEmailAsVerified($event->socialiteUser->getUser());
    }

    /**
     * Mark the user's primary email as verified.
     */
    private function markEmailAsVerified(?Authenticatable $user): void
    {
        if (! $user instanceof User) {
            return;
        }

        if (! $user->email) {
            return;
        }

        if ($user->hasVerifiedEmail()) {
            return;
        }

        $user->markEmailAsVerified();
    }
}

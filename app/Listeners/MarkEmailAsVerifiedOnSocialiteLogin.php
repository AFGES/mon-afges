<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use DutchCodingCompany\FilamentSocialite\Events\Login;
use DutchCodingCompany\FilamentSocialite\Events\Registered;
use DutchCodingCompany\FilamentSocialite\Events\SocialiteUserConnected;

final class MarkEmailAsVerifiedOnSocialiteLogin
{
    /**
     * Handle the Login event.
     */
    public function handleLogin(Login $event): void
    {
        $this->markEmailAsVerified($event->socialiteUser->getUser());
    }

    /**
     * Handle the Registered event.
     */
    public function handleRegistered(Registered $event): void
    {
        $this->markEmailAsVerified($event->socialiteUser->getUser());
    }

    /**
     * Handle the SocialiteUserConnected event.
     */
    public function handleSocialiteUserConnected(SocialiteUserConnected $event): void
    {
        $this->markEmailAsVerified($event->socialiteUser->getUser());
    }

    /**
     * Mark the user's primary email as verified.
     */
    private function markEmailAsVerified(User $user): void
    {
        if (! $user) {
            return;
        }

        // Get or create the primary email
        if ($user->email && ! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }
    }
}

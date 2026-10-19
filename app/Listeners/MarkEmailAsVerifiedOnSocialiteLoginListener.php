<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use DutchCodingCompany\FilamentSocialite\Events\Login;
use DutchCodingCompany\FilamentSocialite\Events\Registered;
use DutchCodingCompany\FilamentSocialite\Events\SocialiteUserConnected;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\Authenticatable;

final class MarkEmailAsVerifiedOnSocialiteLoginListener
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
     *
     * The Verified event links the organization by email domain, which the earlier Login event skipped.
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

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }
    }
}

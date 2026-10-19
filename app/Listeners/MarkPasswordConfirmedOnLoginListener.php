<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\SessionGuard;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;

/**
 * Treat a fresh interactive login (magic link, Google, password or passkey) as a password
 * confirmation, so users without a password can pass the `password.confirm` middleware
 * that guards passkey management until `auth.password_timeout` expires.
 *
 * Every Login event fired today is interactive, so a future programmatic login (e.g.
 * impersonation) must be excluded here.
 */
final class MarkPasswordConfirmedOnLoginListener
{
    public function handle(Login $event): void
    {
        $guard = Auth::guard($event->guard);

        if (! $guard instanceof SessionGuard) {
            return;
        }

        if ($guard->viaRemember()) {
            return;
        }

        $session = $guard->getSession();

        if (! $session instanceof Store) {
            return;
        }

        $session->passwordConfirmed();
    }
}

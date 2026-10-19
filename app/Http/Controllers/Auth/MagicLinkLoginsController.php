<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class MagicLinkLoginsController
{
    /**
     * Verify the magic link token and log in the user.
     *
     * Opening the link proves ownership of the address, so the email is marked
     * as verified before the login links the organization by email domain.
     */
    public function show(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
        ]);

        $user = User::where('email', Str::lower($request->string('email')->toString()))->first();

        if (! $this->hasValidMagicLink($user, $request->token)) {
            throw ValidationException::withMessages([
                'email' => ['Lien invalide ou expiré.'],
            ]);
        }

        /** @var User $user */
        $user->update([
            'magic_link_token' => null,
            'magic_link_token_expires_at' => null,
        ]);

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        Auth::login($user, remember: true);

        $request->session()->regenerate();

        return redirect()->intended(filament()->getUrl());
    }

    /**
     * Check that the user has an unexpired magic link token matching the given token.
     */
    private function hasValidMagicLink(?User $user, string $token): bool
    {
        if (! $user) {
            return false;
        }

        if (! $user->magic_link_token) {
            return false;
        }

        if (! $user->magic_link_token_expires_at) {
            return false;
        }

        if ($user->magic_link_token_expires_at->isPast()) {
            return false;
        }

        return Hash::check($token, $user->magic_link_token);
    }
}

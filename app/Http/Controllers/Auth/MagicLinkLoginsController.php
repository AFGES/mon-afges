<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class MagicLinkLoginsController
{
    /**
     * Verify the magic link token and log in the user.
     */
    public function show(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

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

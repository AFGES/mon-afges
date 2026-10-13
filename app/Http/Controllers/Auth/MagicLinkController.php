<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class MagicLinkController
{
    /**
     * Request a magic link to be sent via email.
     */
    public function request(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $token = Str::random(64);

            $user->update([
                'magic_link_token' => Hash::make($token),
                'magic_link_token_expires_at' => now()->addMinutes(15),
            ]);

            $user->notify(new MagicLinkNotification($token));
        }

        return back()->with('status', 'Si un compte existe avec cette adresse e-mail, un lien de connexion vous a été envoyé.');
    }

    /**
     * Verify the magic link token and log in the user.
     */
    public function verify(Request $request): RedirectResponse
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

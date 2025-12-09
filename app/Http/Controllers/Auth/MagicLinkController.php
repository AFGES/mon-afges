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

        if (! $user) {
            // Don't reveal whether the user exists or not for security
            return back()->with('status', 'Si un compte existe avec cette adresse e-mail, un lien de connexion vous a été envoyé.');
        }

        // Generate a random token
        $token = Str::random(64);

        // Store hashed token and expiration
        $user->update([
            'magic_link_token' => Hash::make($token),
            'magic_link_token_expires_at' => now()->addMinutes(15),
        ]);

        // Send the notification
        $user->notify(new MagicLinkNotification($token));

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

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['Lien invalide ou expiré.'],
            ]);
        }

        // Check if token exists and hasn't expired
        if (
            ! $user->magic_link_token ||
            ! $user->magic_link_token_expires_at ||
            $user->magic_link_token_expires_at->isPast()
        ) {
            throw ValidationException::withMessages([
                'email' => ['Lien invalide ou expiré.'],
            ]);
        }

        // Verify the token
        if (! Hash::check($request->token, $user->magic_link_token)) {
            throw ValidationException::withMessages([
                'email' => ['Lien invalide ou expiré.'],
            ]);
        }

        // Clear the token after successful verification
        $user->update([
            'magic_link_token' => null,
            'magic_link_token_expires_at' => null,
        ]);

        // Log in the user
        Auth::login($user, remember: true);

        $request->session()->regenerate();

        // Redirect to the admin panel
        return redirect()->intended(filament()->getUrl());
    }
}

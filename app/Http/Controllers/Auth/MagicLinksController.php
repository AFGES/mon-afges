<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class MagicLinksController
{
    /**
     * Send a magic link by email.
     */
    public function store(Request $request): RedirectResponse
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
}

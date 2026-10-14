<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\SendMagicLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class MagicLinksController
{
    /**
     * Send a magic link by email.
     */
    public function store(Request $request, SendMagicLink $sendMagicLink): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $sendMagicLink->handle($request->string('email')->toString(), $request->ip());

        return back()->with('status', 'Si un compte existe avec cette adresse e-mail, un lien de connexion vous a été envoyé.');
    }
}

<?php

declare(strict_types=1);

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Livewire\livewire;

it('marks the password as confirmed on a magic link login', function () {
    $this->freezeTime();
    $user = User::factory()->create([
        'email' => 'test@afges.org',
        'magic_link_token' => Hash::make('secret-token'),
        'magic_link_token_expires_at' => now()->addMinutes(15),
    ]);

    $this->get(URL::temporarySignedRoute('magicLink.verify', now()->addMinutes(15), [
        'email' => $user->email,
        'token' => 'secret-token',
    ]))->assertSessionHas('auth.password_confirmed_at', now()->unix());

    assertAuthenticatedAs($user);
});

it('marks the password as confirmed on a password login', function () {
    $this->withoutDefer();
    $this->freezeTime();
    $user = User::factory()->create([
        'email' => 'test@afges.org',
        'password' => 'password',
    ]);

    livewire(Login::class)
        ->set('showMagicLinkForm', false)
        ->set('data.email', 'test@afges.org')
        ->set('data.password', 'password')
        ->call('authenticate');

    assertAuthenticatedAs($user);
    expect(session('auth.password_confirmed_at'))->toBe(now()->unix());
});

it('does not mark the password as confirmed on a remember cookie login', function () {
    $user = User::factory()->create([
        'email' => 'test@afges.org',
        'remember_token' => Str::random(60),
    ]);

    $this->withCookie(
        Auth::guard('web')->getRecallerName(),
        $user->id.'|'.$user->remember_token.'|'.$user->getAuthPassword(),
    )->get('/')->assertSessionMissing('auth.password_confirmed_at');

    assertAuthenticatedAs($user);
});

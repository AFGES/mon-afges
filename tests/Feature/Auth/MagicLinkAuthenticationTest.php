<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

test('user can request a magic link', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'test@afges.org',
    ]);

    $response = $this->post(route('magic-link.request'), [
        'email' => 'test@afges.org',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');

    $user->refresh();

    expect($user->magic_link_token)->not->toBeNull()
        ->and($user->magic_link_token_expires_at)->not->toBeNull()
        ->and($user->magic_link_token_expires_at->isFuture())->toBeTrue();

    Notification::assertSentTo($user, MagicLinkNotification::class);
});

test('requesting magic link for non-existent user returns success without sending', function () {
    Notification::fake();

    $response = $this->post(route('magic-link.request'), [
        'email' => 'nonexistent@afges.org',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');

    Notification::assertNothingSent();
});

test('user can authenticate with valid magic link token', function () {
    $user = User::factory()->create([
        'email' => 'test@afges.org',
    ]);

    $token = Illuminate\Support\Str::random(64);
    $user->update([
        'magic_link_token' => Hash::make($token),
        'magic_link_token_expires_at' => now()->addMinutes(15),
    ]);

    $url = URL::temporarySignedRoute(
        'magic-link.verify',
        now()->addMinutes(15),
        [
            'email' => $user->email,
            'token' => $token,
        ]
    );

    $response = $this->get($url);

    $response->assertRedirect('/');
    $this->assertAuthenticatedAs($user);

    $user->refresh();
    expect($user->magic_link_token)->toBeNull()
        ->and($user->magic_link_token_expires_at)->toBeNull();
});

test('user cannot authenticate with invalid token', function () {
    $user = User::factory()->create([
        'email' => 'test@afges.org',
    ]);

    $token = Illuminate\Support\Str::random(64);
    $user->update([
        'magic_link_token' => Hash::make($token),
        'magic_link_token_expires_at' => now()->addMinutes(15),
    ]);

    $url = URL::temporarySignedRoute(
        'magic-link.verify',
        now()->addMinutes(15),
        [
            'email' => $user->email,
            'token' => 'wrong-token',
        ]
    );

    $response = $this->get($url);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('user cannot authenticate with expired token', function () {
    $user = User::factory()->create([
        'email' => 'test@afges.org',
    ]);

    $token = Illuminate\Support\Str::random(64);
    $user->update([
        'magic_link_token' => Hash::make($token),
        'magic_link_token_expires_at' => now()->subMinutes(1), // Expired
    ]);

    $url = URL::temporarySignedRoute(
        'magic-link.verify',
        now()->addMinutes(15),
        [
            'email' => $user->email,
            'token' => $token,
        ]
    );

    $response = $this->get($url);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('magic link verification requires valid signature', function () {
    $user = User::factory()->create([
        'email' => 'test@afges.org',
    ]);

    $token = Illuminate\Support\Str::random(64);
    $user->update([
        'magic_link_token' => Hash::make($token),
        'magic_link_token_expires_at' => now()->addMinutes(15),
    ]);

    // Create URL without signature
    $response = $this->get(route('magic-link.verify', [
        'email' => $user->email,
        'token' => $token,
    ]));

    $response->assertStatus(403); // Forbidden - invalid signature
    $this->assertGuest();
});

test('magic link token is cleared after successful authentication', function () {
    $user = User::factory()->create([
        'email' => 'test@afges.org',
    ]);

    $token = Illuminate\Support\Str::random(64);
    $user->update([
        'magic_link_token' => Hash::make($token),
        'magic_link_token_expires_at' => now()->addMinutes(15),
    ]);

    $url = URL::temporarySignedRoute(
        'magic-link.verify',
        now()->addMinutes(15),
        [
            'email' => $user->email,
            'token' => $token,
        ]
    );

    $this->get($url);

    $user->refresh();
    expect($user->magic_link_token)->toBeNull()
        ->and($user->magic_link_token_expires_at)->toBeNull();
});

test('same magic link cannot be used twice', function () {
    $user = User::factory()->create([
        'email' => 'test@afges.org',
    ]);

    $token = Illuminate\Support\Str::random(64);
    $user->update([
        'magic_link_token' => Hash::make($token),
        'magic_link_token_expires_at' => now()->addMinutes(15),
    ]);

    $url = URL::temporarySignedRoute(
        'magic-link.verify',
        now()->addMinutes(15),
        [
            'email' => $user->email,
            'token' => $token,
        ]
    );

    // First use - should succeed
    $this->get($url);
    $this->assertAuthenticatedAs($user);

    // Logout
    auth()->logout();

    // Second use - should fail
    $response = $this->get($url);
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

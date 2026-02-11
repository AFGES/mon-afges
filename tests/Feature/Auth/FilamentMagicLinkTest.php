<?php

declare(strict_types=1);

use App\Models\User;

use function Pest\Livewire\livewire;

test('filament login page displays magic link toggle button', function () {
    $response = $this->get('/login');

    $response->assertStatus(200)
        ->assertSee('Se connecter avec un mot de passe');
});

test('filament login page can toggle between password and magic link forms', function () {
    $component = livewire(\App\Filament\Pages\Auth\Login::class);

    // Initial state - showing magic link form
    expect($component->showMagicLinkForm)->toBeTrue();

    // Toggle to password form
    $component->call('toggleMagicLinkForm');
    expect($component->showMagicLinkForm)->toBeFalse();

    // Toggle back to magic link form
    $component->call('toggleMagicLinkForm');
    expect($component->showMagicLinkForm)->toBeTrue();
});

test('filament login sends magic link when in magic link mode', function () {
    $user = User::factory()->create([
        'email' => 'test@afges.org',
    ]);

    livewire(\App\Filament\Pages\Auth\Login::class)
        ->set('showMagicLinkForm', true)
        ->set('data.email', 'test@afges.org')
        ->call('authenticate');

    $user->refresh();

    // Check that magic link token was created
    expect($user->magic_link_token)->not->toBeNull()
        ->and($user->magic_link_token_expires_at)->not->toBeNull();

    // User should not be authenticated yet (needs to click the link)
    expect(auth()->check())->toBeFalse();
});

test('filament login authenticates normally when not in magic link mode', function () {
    $user = User::factory()->create([
        'email' => 'test@afges.org',
        'password' => 'password',
    ]);

    livewire(\App\Filament\Pages\Auth\Login::class)
        ->set('showMagicLinkForm', false)
        ->set('data.email', 'test@afges.org')
        ->set('data.password', 'password')
        ->call('authenticate');

    // User should be authenticated
    expect(auth()->check())->toBeTrue()
        ->and(auth()->id())->toBe($user->id);
});

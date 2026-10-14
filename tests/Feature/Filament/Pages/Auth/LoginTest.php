<?php

declare(strict_types=1);

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Livewire\livewire;

it('renders the login page with the password toggle label', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee(__('login.toggle.password'));
});

it('toggles between the magic link and password forms', function () {
    livewire(Login::class)
        ->assertSet('showMagicLinkForm', true)
        ->call('toggleMagicLinkForm')
        ->assertSet('showMagicLinkForm', false)
        ->call('toggleMagicLinkForm')
        ->assertSet('showMagicLinkForm', true);
});

it('sends a magic link to a known email without signing the user in', function () {
    $this->freezeSecond();
    Notification::fake();
    $user = User::factory()->create(['email' => 'test@afges.org']);

    livewire(Login::class)
        ->set('data.email', 'test@afges.org')
        ->call('authenticate')
        ->assertNotified(__('login.magic_link.sent.title'));

    Notification::assertSentTo($user, MagicLinkNotification::class);
    expect($user->refresh()->magic_link_token)->not->toBeNull()
        ->and($user->magic_link_token_expires_at)->toEqual(now()->addMinutes(15));
    assertGuest();
});

it('shows the same confirmation for an unknown email without sending anything', function () {
    Notification::fake();

    livewire(Login::class)
        ->set('data.email', 'unknown@afges.org')
        ->call('authenticate')
        ->assertNotified(__('login.magic_link.sent.title'));

    Notification::assertNothingSent();
    assertGuest();
});

it('authenticates with the password when the password form is shown', function () {
    Notification::fake();
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
    Notification::assertNothingSent();
});

it('rejects a wrong password', function () {
    User::factory()->create([
        'email' => 'test@afges.org',
        'password' => 'password',
    ]);

    livewire(Login::class)
        ->set('showMagicLinkForm', false)
        ->set('data.email', 'test@afges.org')
        ->set('data.password', 'wrong-password')
        ->call('authenticate')
        ->assertHasErrors(['data.email']);

    assertGuest();
});

it('limits magic links per email and shows the rate limited notification', function () {
    $this->freezeSecond();
    Notification::fake();
    $user = User::factory()->create(['email' => 'test@afges.org']);

    foreach (range(1, 5) as $attempt) {
        livewire(Login::class)
            ->set('data.email', 'test@afges.org')
            ->call('authenticate')
            ->assertNotified(__('login.magic_link.sent.title'));
    }

    livewire(Login::class)
        ->set('data.email', 'test@afges.org')
        ->call('authenticate')
        ->assertNotified(__('filament-panels::auth/pages/login.notifications.throttled.title', [
            'seconds' => 3600,
            'minutes' => 60,
        ]));

    Notification::assertSentToTimes($user, MagicLinkNotification::class, 5);
});

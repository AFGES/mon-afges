<?php

declare(strict_types=1);

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\Register;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Sleep;

use function Pest\Laravel\assertGuest;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->withoutDefer();
    Sleep::fake();
    Notification::fake();
});

it('renders the register page linked from the login page', function () {
    $this->get('/register')->assertOk();

    $this->get('/login')
        ->assertOk()
        ->assertSee(filament()->getRegistrationUrl());
});

it('creates an unverified user without password and sends a magic link without signing in', function () {
    livewire(Register::class)
        ->fillForm([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.org',
        ])
        ->call('register')
        ->assertHasNoFormErrors()
        ->assertNotified(__('register.magic_link.sent.title'));

    $user = User::query()->where('email', 'jane@example.org')->sole();
    expect($user->first_name)->toBe('Jane')
        ->and($user->last_name)->toBe('Doe')
        ->and($user->password)->toBeNull()
        ->and($user->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, MagicLinkNotification::class);
    assertGuest();
});

it('does not duplicate a known email and still sends the link with the same notification', function () {
    $user = User::factory()->create([
        'first_name' => 'Jane',
        'email' => 'jane@example.org',
    ]);

    livewire(Register::class)
        ->fillForm([
            'first_name' => 'Other',
            'last_name' => 'Person',
            'email' => 'JANE@example.org',
        ])
        ->call('register')
        ->assertHasNoFormErrors()
        ->assertNotified(__('register.magic_link.sent.title'));

    $this->assertDatabaseCount('users', 1);
    expect($user->refresh()->first_name)->toBe('Jane');
    Notification::assertSentTo($user, MagicLinkNotification::class);
    assertGuest();
});

it('stores the email in lowercase so the login page finds it', function () {
    livewire(Register::class)
        ->fillForm(['first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'Jane.Doe@Example.org'])
        ->call('register');

    $user = User::query()->where('email', 'jane.doe@example.org')->sole();

    livewire(Login::class)
        ->set('data.email', 'jane.doe@example.org')
        ->call('authenticate');

    Notification::assertSentToTimes($user, MagicLinkNotification::class, 2);
});

it('replaces the names of an unverified account but keeps those of a verified one', function (bool $verified, string $expectedFirstName) {
    $user = User::factory()
        ->state(['first_name' => 'Squatter', 'email' => 'jane@example.org'])
        ->when(! $verified, fn ($factory) => $factory->unverified())
        ->create();

    livewire(Register::class)
        ->fillForm(['first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'jane@example.org'])
        ->call('register')
        ->assertNotified(__('register.magic_link.sent.title'));

    expect($user->refresh()->first_name)->toBe($expectedFirstName);
})->with([
    'unverified' => [false, 'Jane'],
    'verified' => [true, 'Squatter'],
]);

it('validates the form', function (array $data, array $errors) {
    livewire(Register::class)
        ->fillForm([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.org',
            ...$data,
        ])
        ->call('register')
        ->assertHasFormErrors($errors);

    $this->assertDatabaseCount('users', 0);
    Notification::assertNothingSent();
})->with([
    'missing first name' => [['first_name' => ''], ['first_name' => 'required']],
    'missing last name' => [['last_name' => ''], ['last_name' => 'required']],
    'missing email' => [['email' => ''], ['email' => 'required']],
    'invalid email' => [['email' => 'not-an-email'], ['email' => 'email']],
]);

it('limits registrations and shows the rate limited notification', function () {
    $component = livewire(Register::class);

    foreach (['first@example.org', 'second@example.org'] as $email) {
        $component
            ->fillForm(['first_name' => 'Jane', 'last_name' => 'Doe', 'email' => $email])
            ->call('register')
            ->assertNotified(__('register.magic_link.sent.title'));
    }

    $component
        ->fillForm(['first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'third@example.org'])
        ->call('register')
        ->assertNotified(__('filament-panels::auth/pages/register.notifications.throttled.title', [
            'seconds' => 60,
            'minutes' => 1,
        ]));

    $this->assertDatabaseMissing('users', ['email' => 'third@example.org']);
});

<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Sleep;

const MAGIC_LINK_STATUS = 'Si un compte existe avec cette adresse e-mail, un lien de connexion vous a été envoyé.';

beforeEach(function () {
    $this->withoutDefer();
    Sleep::fake();
});

it('stores a hashed token valid for 15 minutes and sends the magic link', function () {
    $this->freezeTime();
    Notification::fake([MagicLinkNotification::class]);
    $user = User::factory()->create(['email' => 'test@afges.org']);

    $response = $this->post(route('magicLink.request'), ['email' => 'test@afges.org']);

    $response->assertRedirect()->assertSessionHas('status', MAGIC_LINK_STATUS);
    $user->refresh();
    expect($user->magic_link_token)->not->toBeNull()
        ->and($user->magic_link_token_expires_at->toDateTimeString())->toBe(now()->addMinutes(15)->toDateTimeString());
    Notification::assertSentTo(
        $user,
        MagicLinkNotification::class,
        function (MagicLinkNotification $notification) use ($user): bool {
            parse_str((string) parse_url(templateMailLinks($notification->toMail($user))[0], PHP_URL_QUERY), $query);

            return Hash::check($query['token'], $user->magic_link_token);
        },
    );
});

it('answers the same status without sending anything for an unknown email', function () {
    Notification::fake([MagicLinkNotification::class]);

    $response = $this->post(route('magicLink.request'), ['email' => 'nonexistent@afges.org']);

    $response->assertRedirect()->assertSessionHas('status', MAGIC_LINK_STATUS);
    Notification::assertNothingSent();
});

it('rejects an invalid email', function (array $payload, string $message) {
    app()->setLocale('en');
    Notification::fake([MagicLinkNotification::class]);

    $response = $this->post(route('magicLink.request'), $payload);

    $response->assertSessionHasErrors(['email' => $message]);
    Notification::assertNothingSent();
})->with([
    'missing email' => [[], 'The email field is required.'],
    'malformed email' => [['email' => 'not-an-email'], 'The email field must be a valid email address.'],
]);

it('redirects authenticated users away from the request endpoint', function () {
    Notification::fake([MagicLinkNotification::class]);
    $user = User::factory()->create(['email' => 'test@afges.org']);

    $response = $this->actingAs($user)->post(route('magicLink.request'), ['email' => 'test@afges.org']);

    $response->assertRedirect();
    Notification::assertNothingSent();
    expect($user->refresh()->magic_link_token)->toBeNull();
});

it('limits magic link requests to five per hour per email across different ips', function () {
    Notification::fake([MagicLinkNotification::class]);
    User::factory()->create(['email' => 'test@afges.org']);

    foreach (range(1, 5) as $attempt) {
        $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.{$attempt}"])
            ->post(route('magicLink.request'), ['email' => 'test@afges.org'])
            ->assertRedirect();
    }
    $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
        ->post(route('magicLink.request'), ['email' => ' TEST@afges.org ']);

    $response->assertTooManyRequests();
    Notification::assertSentTimes(MagicLinkNotification::class, 5);
});

it('limits magic link requests to thirty per hour per ip across different emails', function () {
    Notification::fake([MagicLinkNotification::class]);

    foreach (range(1, 30) as $attempt) {
        $this->post(route('magicLink.request'), ['email' => "user{$attempt}@afges.org"])->assertRedirect();
    }
    $response = $this->post(route('magicLink.request'), ['email' => 'user31@afges.org']);

    $response->assertTooManyRequests();
    Notification::assertNothingSent();
});

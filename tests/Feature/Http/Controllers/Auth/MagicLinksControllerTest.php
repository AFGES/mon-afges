<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

const MAGIC_LINK_STATUS = 'Si un compte existe avec cette adresse e-mail, un lien de connexion vous a été envoyé.';

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
            parse_str((string) parse_url($notification->toMail($user)->actionUrl, PHP_URL_QUERY), $query);

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

it('throttles magic link requests after five attempts per email and ip', function () {
    Notification::fake([MagicLinkNotification::class]);
    User::factory()->create(['email' => 'test@afges.org']);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('magicLink.request'), ['email' => 'test@afges.org'])->assertRedirect();
    }
    $response = $this->post(route('magicLink.request'), ['email' => 'test@afges.org']);

    $response->assertTooManyRequests();
    Notification::assertSentTimes(MagicLinkNotification::class, 5);
});

it('does not share the throttle between different emails', function () {
    Notification::fake([MagicLinkNotification::class]);
    User::factory()->create(['email' => 'test@afges.org']);
    User::factory()->create(['email' => 'other@afges.org']);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('magicLink.request'), ['email' => 'test@afges.org']);
    }
    $response = $this->post(route('magicLink.request'), ['email' => 'other@afges.org']);

    $response->assertRedirect();
    Notification::assertSentTimes(MagicLinkNotification::class, 6);
});

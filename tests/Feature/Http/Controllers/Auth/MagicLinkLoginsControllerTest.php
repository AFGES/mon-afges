<?php

declare(strict_types=1);

use App\Enums\ApprovalStatus;
use App\Models\Organization;
use App\Models\User;
use App\Models\Volunteer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;

/**
 * @return array{0: User, 1: string}
 */
function userWithPendingMagicLink(?User $user = null): array
{
    $user ??= User::factory()->create(['email' => 'test@afges.org']);
    $token = 'known-magic-link-token';
    $user->update([
        'magic_link_token' => Hash::make($token),
        'magic_link_token_expires_at' => now()->addMinutes(15),
    ]);

    return [$user, $token];
}

/**
 * @param  array<string, string>  $parameters
 */
function signedMagicLinkUrl(array $parameters): string
{
    return URL::temporarySignedRoute('magicLink.verify', now()->addMinutes(15), $parameters);
}

it('logs the user in, redirects home and consumes the token', function () {
    [$user, $token] = userWithPendingMagicLink();

    $response = $this->get(signedMagicLinkUrl(['email' => $user->email, 'token' => $token]));

    $response->assertRedirect('/');
    $this->assertAuthenticatedAs($user);
    expect($user->refresh()->magic_link_token)->toBeNull()
        ->and($user->magic_link_token_expires_at)->toBeNull();
});

it('verifies the email and links the organization by domain on the first login', function () {
    $organization = Organization::factory()->create(['domain' => 'afges.org']);
    [$user, $token] = userWithPendingMagicLink(User::factory()->unverified()->create(['email' => 'new@afges.org']));

    $this->get(signedMagicLinkUrl(['email' => $user->email, 'token' => $token]));

    $this->assertAuthenticatedAs($user);
    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
    $volunteer = Volunteer::query()->where('user_id', $user->id)->sole();
    expect($volunteer->organization_id)->toBe($organization->id)
        ->and($volunteer->approval_status)->toBe(ApprovalStatus::Approved);
});

it('rejects a link that is used a second time', function () {
    [$user, $token] = userWithPendingMagicLink();
    $url = signedMagicLinkUrl(['email' => $user->email, 'token' => $token]);
    $this->get($url);
    auth()->logout();

    $response = $this->get($url);

    $response->assertSessionHasErrors(['email' => 'Lien invalide ou expiré.']);
    $this->assertGuest();
});

it('rejects a wrong token and keeps the stored token', function () {
    [$user] = userWithPendingMagicLink();
    $storedToken = $user->magic_link_token;

    $response = $this->get(signedMagicLinkUrl(['email' => $user->email, 'token' => 'wrong-token']));

    $response->assertSessionHasErrors(['email' => 'Lien invalide ou expiré.']);
    $this->assertGuest();
    expect($user->refresh()->magic_link_token)->toBe($storedToken);
});

it('rejects a token that expired on the server side', function () {
    [$user, $token] = userWithPendingMagicLink();
    $user->update(['magic_link_token_expires_at' => now()->subMinute()]);

    $response = $this->get(signedMagicLinkUrl(['email' => $user->email, 'token' => $token]));

    $response->assertSessionHasErrors(['email' => 'Lien invalide ou expiré.']);
    $this->assertGuest();
});

it('rejects an email that matches no user', function () {
    [, $token] = userWithPendingMagicLink();

    $response = $this->get(signedMagicLinkUrl(['email' => 'unknown@afges.org', 'token' => $token]));

    $response->assertSessionHasErrors(['email' => 'Lien invalide ou expiré.']);
    $this->assertGuest();
});

it('rejects a user who has no pending magic link', function () {
    $user = User::factory()->create(['email' => 'test@afges.org']);

    $response = $this->get(signedMagicLinkUrl(['email' => $user->email, 'token' => 'any-token']));

    $response->assertSessionHasErrors(['email' => 'Lien invalide ou expiré.']);
    $this->assertGuest();
});

it('returns 403 when the url is not signed', function () {
    [$user, $token] = userWithPendingMagicLink();

    $response = $this->get(route('magicLink.verify', ['email' => $user->email, 'token' => $token]));

    $response->assertForbidden();
    $this->assertGuest();
});

it('returns 403 when the signed url has expired', function () {
    $this->freezeTime();
    [$user, $token] = userWithPendingMagicLink();
    $url = signedMagicLinkUrl(['email' => $user->email, 'token' => $token]);

    $this->travel(16)->minutes();
    $response = $this->get($url);

    $response->assertForbidden();
    $this->assertGuest();
});

it('returns 403 when the signed email is tampered with', function () {
    [$user, $token] = userWithPendingMagicLink();
    $other = User::factory()->create(['email' => 'other@afges.org']);
    $url = str_replace(urlencode($user->email), urlencode($other->email), signedMagicLinkUrl(['email' => $user->email, 'token' => $token]));

    $response = $this->get($url);

    $response->assertForbidden();
    $this->assertGuest();
});

it('validates the query parameters', function (array $parameters, string $field, string $message) {
    app()->setLocale('en');

    $response = $this->get(signedMagicLinkUrl($parameters));

    $response->assertSessionHasErrors([$field => $message]);
    $this->assertGuest();
})->with([
    'missing email' => [['token' => 'abc'], 'email', 'The email field is required.'],
    'invalid email' => [['email' => 'not-an-email', 'token' => 'abc'], 'email', 'The email field must be a valid email address.'],
    'missing token' => [['email' => 'test@afges.org'], 'token', 'The token field is required.'],
]);

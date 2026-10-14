<?php

declare(strict_types=1);

use App\Actions\CreateUserFromSocialite;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use Laravel\Socialite\Two\User as SocialiteUser;

function createUserFromOauth(array $raw, ?string $name): array
{
    $oauthUser = (new SocialiteUser)->setRaw($raw)->map([
        'name' => $name,
        'email' => 'ada@afges.org',
    ]);

    $user = (new CreateUserFromSocialite)('google', $oauthUser, FilamentSocialitePlugin::make());

    return [$user->first_name, $user->last_name, $user->email];
}

test('maps the google given and family names', function () {
    expect(createUserFromOauth(['given_name' => 'Ada Marie', 'family_name' => 'King'], 'Ignored Name'))
        ->toBe(['Ada Marie', 'King', 'ada@afges.org']);
});

test('falls back to splitting the display name on the first space', function () {
    expect(createUserFromOauth([], 'Ada Augusta King'))
        ->toBe(['Ada', 'Augusta King', 'ada@afges.org']);
});

test('falls back to a placeholder last name for a single word name', function () {
    expect(createUserFromOauth([], 'Ada'))
        ->toBe(['Ada', null, 'ada@afges.org']);
});

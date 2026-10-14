<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use Illuminate\Support\Str;
use Laravel\Socialite\AbstractUser;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;

final class CreateUserFromSocialite
{
    public function __invoke(string $provider, SocialiteUserContract $oauthUser, FilamentSocialitePlugin $plugin): User
    {
        [$firstName, $lastName] = $this->resolveNames($oauthUser);

        return User::query()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $oauthUser->getEmail(),
        ]);
    }

    /**
     * Prefer the provider's given and family names, falling back to splitting the display name on its first space.
     *
     * @return array{0: string, 1: ?string}
     */
    private function resolveNames(SocialiteUserContract $oauthUser): array
    {
        $raw = $oauthUser instanceof AbstractUser ? $oauthUser->getRaw() : [];
        $givenName = Str::squish((string) ($raw['given_name'] ?? ''));
        $familyName = Str::squish((string) ($raw['family_name'] ?? ''));

        if ($givenName !== '' && $familyName !== '') {
            return [$givenName, $familyName];
        }

        $fullName = Str::squish((string) $oauthUser->getName());

        if ($fullName === '') {
            $fullName = Str::before((string) $oauthUser->getEmail(), '@');
        }

        return [
            Str::before($fullName, ' '),
            Str::contains($fullName, ' ') ? Str::after($fullName, ' ') : null,
        ];
    }
}

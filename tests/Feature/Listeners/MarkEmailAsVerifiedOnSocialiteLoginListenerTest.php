<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\SocialiteUser;
use App\Models\User;
use App\Models\Volunteer;
use DutchCodingCompany\FilamentSocialite\Events\Login;
use Filament\Facades\Filament;
use Laravel\Socialite\Two\User as OAuthUser;

it('verifies the email and links the organization by domain on a Google login', function () {
    Filament::setCurrentPanel('root');
    $organization = Organization::factory()->create(['domain' => 'afges.org']);
    $user = User::factory()->unverified()->create(['email' => 'jane@afges.org']);
    $socialiteUser = SocialiteUser::create([
        'user_id' => $user->id,
        'provider' => 'google',
        'provider_id' => 'google-id',
    ]);

    event(new Login($socialiteUser, new OAuthUser));

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue()
        ->and(Volunteer::query()->where('user_id', $user->id)->sole()->organization_id)->toBe($organization->id);
});

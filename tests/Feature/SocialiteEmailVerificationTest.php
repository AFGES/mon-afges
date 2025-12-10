<?php

declare(strict_types=1);

use App\Listeners\MarkEmailAsVerifiedOnSocialiteLogin;
use App\Models\Email;
use App\Models\User;
use DutchCodingCompany\FilamentSocialite\Events\Login;
use DutchCodingCompany\FilamentSocialite\Events\Registered;
use DutchCodingCompany\FilamentSocialite\Events\SocialiteUserConnected;
use DutchCodingCompany\FilamentSocialite\Models\SocialiteUser;
use Illuminate\Support\Facades\Event;
use Laravel\Socialite\Two\User as SocialiteOAuthUser;

beforeEach(function () {
    $this->listener = new MarkEmailAsVerifiedOnSocialiteLogin;
});

test('email is marked as verified when user logs in with socialite', function () {
    $user = User::factory()->unverified()->create([
        'email' => 'test@afges.org',
    ]);

    // Ensure primary email is not verified
    $primaryEmail = $user->primaryEmail();
    expect($primaryEmail)->not->toBeNull();
    expect($primaryEmail->isVerified())->toBeFalse();

    // Create a mock socialite user
    $socialiteUser = new SocialiteUser([
        'user_id' => $user->id,
        'provider' => 'google',
        'provider_id' => '12345',
    ]);
    $socialiteUser->setRelation('user', $user);

    // Create a mock OAuth user
    $oauthUser = new SocialiteOAuthUser;

    // Create the Login event
    $event = new Login($socialiteUser, $oauthUser);

    // Handle the event
    $this->listener->handleLogin($event);

    // Verify email is now marked as verified
    $primaryEmail->refresh();
    expect($primaryEmail->isVerified())->toBeTrue();
    expect($primaryEmail->verified_at)->not->toBeNull();
});

test('email is marked as verified when user registers with socialite', function () {
    $user = User::factory()->unverified()->create([
        'email' => 'newuser@afges.org',
    ]);

    $primaryEmail = $user->primaryEmail();
    expect($primaryEmail)->not->toBeNull();
    expect($primaryEmail->isVerified())->toBeFalse();

    // Create a mock socialite user
    $socialiteUser = new SocialiteUser([
        'user_id' => $user->id,
        'provider' => 'google',
        'provider_id' => '67890',
    ]);
    $socialiteUser->setRelation('user', $user);

    // Create a mock OAuth user
    $oauthUser = new SocialiteOAuthUser;

    // Create the Registered event
    $event = new Registered('google', $oauthUser, $socialiteUser);

    // Handle the event
    $this->listener->handleRegistered($event);

    // Verify email is now marked as verified
    $primaryEmail->refresh();
    expect($primaryEmail->isVerified())->toBeTrue();
});

test('email is marked as verified when socialite user is connected', function () {
    $user = User::factory()->unverified()->create([
        'email' => 'existing@afges.org',
    ]);

    $primaryEmail = $user->primaryEmail();
    expect($primaryEmail)->not->toBeNull();
    expect($primaryEmail->isVerified())->toBeFalse();

    // Create a mock socialite user
    $socialiteUser = new SocialiteUser([
        'user_id' => $user->id,
        'provider' => 'google',
        'provider_id' => '11111',
    ]);
    $socialiteUser->setRelation('user', $user);

    // Create a mock OAuth user
    $oauthUser = new SocialiteOAuthUser;

    // Create the SocialiteUserConnected event
    $event = new SocialiteUserConnected('google', $oauthUser, $socialiteUser);

    // Handle the event
    $this->listener->handleSocialiteUserConnected($event);

    // Verify email is now marked as verified
    $primaryEmail->refresh();
    expect($primaryEmail->isVerified())->toBeTrue();
});

test('listener does not fail when primary email is already verified', function () {
    $user = User::factory()->create([
        'email' => 'verified@afges.org',
    ]);

    // Email should already be verified from factory
    $primaryEmail = $user->primaryEmail();
    expect($primaryEmail)->not->toBeNull();
    expect($primaryEmail->isVerified())->toBeTrue();
    $originalVerifiedAt = $primaryEmail->verified_at;

    // Create a mock socialite user
    $socialiteUser = new SocialiteUser([
        'user_id' => $user->id,
        'provider' => 'google',
        'provider_id' => '99999',
    ]);
    $socialiteUser->setRelation('user', $user);

    // Create a mock OAuth user
    $oauthUser = new SocialiteOAuthUser;

    // Create the Login event
    $event = new Login($socialiteUser, $oauthUser);

    // Handle the event
    $this->listener->handleLogin($event);

    // Verify email is still verified and timestamp hasn't changed
    $primaryEmail->refresh();
    expect($primaryEmail->isVerified())->toBeTrue();
    expect($primaryEmail->verified_at->eq($originalVerifiedAt))->toBeTrue();
});

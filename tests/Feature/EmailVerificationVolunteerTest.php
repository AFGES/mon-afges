<?php

declare(strict_types=1);

use App\Models\Email;
use App\Models\Organization;
use App\Models\User;
use App\Models\Volunteer;
use App\Services\EmailVerificationVolunteerService;

use function Pest\Laravel\assertDatabaseHas;

it('creates volunteer when email with matching domain is verified', function () {
    $organization = Organization::factory()->create(['domain' => 'example.com']);
    $user = User::factory()->create();
    $email = Email::factory()->unverified()->create([
        'user_id' => $user->id,
        'email' => 'test@example.com',
    ]);

    expect(Volunteer::withoutGlobalScopes()->count())->toBe(0);

    $email->markAsVerified();

    expect(Volunteer::withoutGlobalScopes()->count())->toBe(1);

    $volunteer = Volunteer::withoutGlobalScopes()->first();
    expect($volunteer->user_id)->toBe($user->id);
    expect($volunteer->organization_id)->toBe($organization->id);
    expect($volunteer->position_title)->toBe('Membre');
    expect($volunteer->started_at)->not->toBeNull();
    expect($volunteer->ended_at)->toBeNull();
});

it('does not create volunteer when email domain does not match any organization', function () {
    Organization::factory()->create(['domain' => 'example.com']);
    $user = User::factory()->create();
    $email = Email::factory()->unverified()->create([
        'user_id' => $user->id,
        'email' => 'test@different.com',
    ]);

    expect(Volunteer::withoutGlobalScopes()->count())->toBe(0);

    $email->markAsVerified();

    expect(Volunteer::withoutGlobalScopes()->count())->toBe(0);
});

it('does not create duplicate volunteer if one already exists', function () {
    $organization = Organization::factory()->create(['domain' => 'example.com']);
    $user = User::factory()->create();

    // Create existing volunteer
    Volunteer::factory()->create([
        'user_id' => $user->id,
        'organization_id' => $organization->id,
        'ended_at' => null,
    ]);

    $email = Email::factory()->unverified()->create([
        'user_id' => $user->id,
        'email' => 'test@example.com',
    ]);

    expect(Volunteer::withoutGlobalScopes()->count())->toBe(1);

    $email->markAsVerified();

    expect(Volunteer::withoutGlobalScopes()->count())->toBe(1);
});

it('extracts domain correctly from email', function () {
    $service = app(EmailVerificationVolunteerService::class);
    $organization = Organization::factory()->create(['domain' => 'subdomain.example.com']);
    $user = User::factory()->create();
    $email = Email::factory()->unverified()->create([
        'user_id' => $user->id,
        'email' => 'user@subdomain.example.com',
    ]);

    $email->markAsVerified();

    expect(Volunteer::withoutGlobalScopes()->count())->toBe(1);
});

it('handles case insensitive domain matching', function () {
    $organization = Organization::factory()->create(['domain' => 'Example.COM']);
    $user = User::factory()->create();
    $email = Email::factory()->unverified()->create([
        'user_id' => $user->id,
        'email' => 'test@EXAMPLE.com',
    ]);

    $email->markAsVerified();

    expect(Volunteer::withoutGlobalScopes()->count())->toBe(1);
    expect(Volunteer::withoutGlobalScopes()->first()->organization_id)->toBe($organization->id);
});

it('does not create volunteer when email is verified but domain is null', function () {
    Organization::factory()->create(['domain' => null]);
    $user = User::factory()->create();
    $email = Email::factory()->unverified()->create([
        'user_id' => $user->id,
        'email' => 'test@example.com',
    ]);

    $email->markAsVerified();

    expect(Volunteer::withoutGlobalScopes()->count())->toBe(0);
});

it('only creates volunteer when verified_at changes', function () {
    $organization = Organization::factory()->create(['domain' => 'example.com']);
    $user = User::factory()->create();
    $email = Email::factory()->verified()->create([
        'user_id' => $user->id,
        'email' => 'test@example.com',
    ]);

    expect(Volunteer::withoutGlobalScopes()->count())->toBe(0);

    // Update something other than verified_at
    $email->is_primary = true;
    $email->save();

    expect(Volunteer::withoutGlobalScopes()->count())->toBe(0);
});

it('service returns existing volunteer if already exists', function () {
    $organization = Organization::factory()->create(['domain' => 'example.com']);
    $user = User::factory()->create();
    $existingVolunteer = Volunteer::factory()->create([
        'user_id' => $user->id,
        'organization_id' => $organization->id,
        'ended_at' => null,
    ]);

    $email = Email::factory()->unverified()->create([
        'user_id' => $user->id,
        'email' => 'test@example.com',
    ]);

    $service = app(EmailVerificationVolunteerService::class);
    $result = $service->handleVerifiedEmail($email);

    expect($result->id)->toBe($existingVolunteer->id);
});

it('service returns null when no matching organization', function () {
    $user = User::factory()->create();
    $email = Email::factory()->unverified()->create([
        'user_id' => $user->id,
        'email' => 'test@nomatch.com',
    ]);

    $service = app(EmailVerificationVolunteerService::class);
    $result = $service->handleVerifiedEmail($email);

    expect($result)->toBeNull();
});

it('creates volunteer in database with correct attributes', function () {
    $organization = Organization::factory()->create(['domain' => 'company.org']);
    $user = User::factory()->create();
    $email = Email::factory()->unverified()->create([
        'user_id' => $user->id,
        'email' => 'employee@company.org',
    ]);

    $email->markAsVerified();

    assertDatabaseHas('volunteers', [
        'user_id' => $user->id,
        'organization_id' => $organization->id,
        'position_title' => 'Membre',
    ]);
});

it('user can have volunteers relationship', function () {
    $organization = Organization::factory()->create(['domain' => 'mycompany.com']);
    $user = User::factory()->create();
    $email = Email::factory()->unverified()->create([
        'user_id' => $user->id,
        'email' => 'worker@mycompany.com',
    ]);

    expect($user->volunteers()->withoutGlobalScopes()->count())->toBe(0);

    $email->markAsVerified();

    $user = $user->fresh();
    expect($user->volunteers()->withoutGlobalScopes()->count())->toBe(1);
    expect($user->volunteers()->withoutGlobalScopes()->first()->organization_id)->toBe($organization->id);
});

<?php

declare(strict_types=1);

use App\Models\Email;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;

it('belongs to a user', function () {
    $user = User::factory()->create();
    $email = Email::factory()->create(['user_id' => $user->id]);

    expect($email->user)->toBeInstanceOf(User::class);
    expect($email->user->id)->toBe($user->id);
});

it('can be marked as primary', function () {
    $user = User::factory()->create();
    $email = Email::factory()->create(['user_id' => $user->id, 'is_primary' => false]);

    expect($email->isPrimary())->toBeFalse();

    $email->markAsPrimary();

    expect($email->fresh()->isPrimary())->toBeTrue();
});

it('ensures only one email is primary per user', function () {
    $user = User::factory()->create();
    $email1 = Email::factory()->primary()->create(['user_id' => $user->id]);
    $email2 = Email::factory()->create(['user_id' => $user->id, 'is_primary' => false]);

    expect($email1->isPrimary())->toBeTrue();
    expect($email2->isPrimary())->toBeFalse();

    $email2->markAsPrimary();

    expect($email1->fresh()->isPrimary())->toBeFalse();
    expect($email2->fresh()->isPrimary())->toBeTrue();
});

it('can be marked as verified', function () {
    $user = User::factory()->create();
    $email = Email::factory()->unverified()->create(['user_id' => $user->id]);

    expect($email->isVerified())->toBeFalse();

    $email->markAsVerified();

    expect($email->fresh()->isVerified())->toBeTrue();
    expect($email->fresh()->verified_at)->not->toBeNull();
});

it('can check if email is verified', function () {
    $user = User::factory()->create();
    $verifiedEmail = Email::factory()->verified()->create(['user_id' => $user->id]);
    $unverifiedEmail = Email::factory()->unverified()->create(['user_id' => $user->id]);

    expect($verifiedEmail->isVerified())->toBeTrue();
    expect($unverifiedEmail->isVerified())->toBeFalse();
});

it('can check if email is primary', function () {
    $user = User::factory()->create();
    $primaryEmail = Email::factory()->primary()->create(['user_id' => $user->id]);
    $secondaryEmail = Email::factory()->create(['user_id' => $user->id, 'is_primary' => false]);

    expect($primaryEmail->isPrimary())->toBeTrue();
    expect($secondaryEmail->isPrimary())->toBeFalse();
});

it('cascades deletion when user is deleted', function () {
    $user = User::factory()->create();
    $email = Email::factory()->create(['user_id' => $user->id]);

    expect(Email::find($email->id))->not->toBeNull();

    $user->delete();

    expect(Email::find($email->id))->toBeNull();
});

it('requires unique email address', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $email = 'duplicate@example.com';

    Email::factory()->create(['user_id' => $user1->id, 'email' => $email]);

    Email::factory()->create(['user_id' => $user2->id, 'email' => $email]);
})->throws(\Illuminate\Database\QueryException::class);

it('allows a user to have multiple emails', function () {
    $user = User::factory()->create();

    // User already has 1 primary email from creation, add 3 more
    Email::factory()->count(3)->create(['user_id' => $user->id]);

    expect($user->fresh()->emails)->toHaveCount(4);
});

it('can retrieve primary email for user', function () {
    $user = User::factory()->create([
        'email' => 'primary@example.com',
    ]);

    // Add additional non-primary emails
    Email::factory()->count(2)->create(['user_id' => $user->id, 'is_primary' => false]);

    $result = $user->primaryEmail();

    expect($result)->not->toBeNull();
    expect($result->email)->toBe('primary@example.com');
    expect($result->isPrimary())->toBeTrue();
});

it('returns null when user has no primary email', function () {
    $user = User::factory()->create();

    // Delete the auto-created primary email
    $user->emails()->delete();

    // Add only non-primary emails
    Email::factory()->count(2)->create([
        'user_id' => $user->id,
        'is_primary' => false,
    ]);

    expect($user->fresh()->primaryEmail())->toBeNull();
});

it('can retrieve verified emails for user', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(), // This makes the auto-created primary email verified
    ]);

    // Add 1 more verified email and 1 unverified
    Email::factory()->verified()->create(['user_id' => $user->id]);
    Email::factory()->unverified()->create(['user_id' => $user->id]);

    expect($user->fresh()->verifiedEmails)->toHaveCount(2);
});

it('updates primary status in database when marked as primary', function () {
    $user = User::factory()->create();
    $email = Email::factory()->create([
        'user_id' => $user->id,
        'is_primary' => false,
    ]);

    $email->markAsPrimary();

    assertDatabaseHas('emails', [
        'id' => $email->id,
        'is_primary' => true,
    ]);
});

it('updates verified_at in database when marked as verified', function () {
    $user = User::factory()->create();
    $email = Email::factory()->unverified()->create(['user_id' => $user->id]);

    $email->markAsVerified();

    assertDatabaseHas('emails', [
        'id' => $email->id,
    ]);

    expect($email->fresh()->verified_at)->not->toBeNull();
});

it('creates primary email when user is created with email field', function () {
    $user = User::factory()->create([
        'email' => 'newuser@example.com',
    ]);

    $primaryEmail = $user->primaryEmail();

    expect($primaryEmail)->not->toBeNull();
    expect($primaryEmail->email)->toBe('newuser@example.com');
    expect($primaryEmail->isPrimary())->toBeTrue();
});

it('updates primary email when user email field is changed', function () {
    $user = User::factory()->create([
        'email' => 'original@example.com',
    ]);

    $originalPrimaryEmail = $user->primaryEmail();
    expect($originalPrimaryEmail->email)->toBe('original@example.com');

    $user->email = 'updated@example.com';
    $user->save();

    $updatedPrimaryEmail = $user->fresh()->primaryEmail();
    expect($updatedPrimaryEmail->id)->toBe($originalPrimaryEmail->id);
    expect($updatedPrimaryEmail->email)->toBe('updated@example.com');
});

it('creates primary email if none exists when user email is updated', function () {
    $user = User::factory()->create();

    // Manually delete the primary email to simulate a user without one
    $user->emails()->delete();

    expect($user->fresh()->primaryEmail())->toBeNull();

    $user->email = 'newprimary@example.com';
    $user->save();

    $primaryEmail = $user->fresh()->primaryEmail();
    expect($primaryEmail)->not->toBeNull();
    expect($primaryEmail->email)->toBe('newprimary@example.com');
    expect($primaryEmail->isPrimary())->toBeTrue();
});

it('syncs email_verified_at to primary email when creating', function () {
    $verifiedAt = now();

    $user = User::factory()->create([
        'email' => 'verified@example.com',
        'email_verified_at' => $verifiedAt,
    ]);

    $primaryEmail = $user->primaryEmail();

    expect($primaryEmail->verified_at)->not->toBeNull();
    expect($primaryEmail->verified_at->timestamp)->toBe($verifiedAt->timestamp);
});

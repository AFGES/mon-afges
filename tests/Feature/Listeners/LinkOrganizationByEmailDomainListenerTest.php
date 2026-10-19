<?php

declare(strict_types=1);

use App\Enums\ApprovalStatus;
use App\Models\Organization;
use App\Models\User;
use App\Models\Volunteer;
use App\Notifications\ApprovalRequestedNotification;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;

function logInUser(string $email): User
{
    $user = User::factory()->create(['email' => $email]);

    event(new Login('web', $user, false));

    return $user;
}

it('creates an approved current volunteer without title or approver notification when the email domain matches', function () {
    $this->freezeTime();
    Permission::create(['name' => 'Update:Volunteer', 'guard_name' => 'web']);
    User::factory()->create()->givePermissionTo('Update:Volunteer');
    $organization = Organization::factory()->create(['domain' => 'afges.org']);
    Notification::fake([ApprovalRequestedNotification::class]);

    $user = logInUser('jane@afges.org');

    $volunteer = Volunteer::where('user_id', $user->id)->sole();
    expect($volunteer->organization_id)->toBe($organization->id)
        ->and($volunteer->position_title)->toBeNull()
        ->and($volunteer->approval_status)->toBe(ApprovalStatus::Approved)
        ->and($volunteer->approved_at->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($volunteer->started_at->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($volunteer->isCurrentlyActive())->toBeTrue();
    Notification::assertNothingSent();
});

it('matches case-insensitively and tolerates a leading at sign', function () {
    $organization = Organization::factory()->create(['domain' => '@AFGES.org']);

    $user = logInUser('Jane@Afges.ORG');

    expect(Volunteer::where('user_id', $user->id)->sole()->organization_id)->toBe($organization->id);
});

it('creates nothing when no organization matches', function () {
    Organization::factory()->create(['domain' => 'afges.org']);
    Organization::factory()->create(['domain' => null]);

    logInUser('jane@example.com');

    $this->assertDatabaseCount('volunteers', 0);
});

it('does not match a domain that only shares a suffix', function () {
    Organization::factory()->create(['domain' => 'afges.org']);

    logInUser('jane@notafges.org');

    $this->assertDatabaseCount('volunteers', 0);
});

it('does not duplicate an existing volunteer, even a pending one', function () {
    $organization = Organization::factory()->create(['domain' => 'afges.org']);
    $user = User::factory()->create(['email' => 'jane@afges.org']);
    $pending = Volunteer::factory()->for($user)->for($organization)->create();

    event(new Login('web', $user, false));

    $this->assertDatabaseCount('volunteers', 1);
    expect($pending->refresh()->isPending())->toBeTrue();
});

it('does not link again a user whose request was rejected', function () {
    $organization = Organization::factory()->create(['domain' => 'afges.org']);
    $user = User::factory()->create(['email' => 'jane@afges.org']);
    $rejected = Volunteer::factory()->rejected()->for($user)->for($organization)->create();

    event(new Login('web', $user, false));

    $this->assertDatabaseCount('volunteers', 1);
    expect($rejected->refresh()->isRejected())->toBeTrue();
});

it('does not duplicate the volunteer on a later login', function () {
    Organization::factory()->create(['domain' => 'afges.org']);
    $user = logInUser('jane@afges.org');

    event(new Login('web', $user, false));

    $this->assertDatabaseCount('volunteers', 1);
});

it('links a user verified after logging in', function () {
    $organization = Organization::factory()->create(['domain' => 'afges.org']);
    $user = User::factory()->unverified()->create(['email' => 'jane@afges.org']);

    event(new Login('web', $user, false));
    $this->assertDatabaseCount('volunteers', 0);

    $user->markEmailAsVerified();
    event(new Verified($user));

    expect(Volunteer::query()->where('user_id', $user->id)->sole()->organization_id)->toBe($organization->id);
});

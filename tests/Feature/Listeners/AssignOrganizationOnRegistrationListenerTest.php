<?php

declare(strict_types=1);

use App\Enums\ApprovalStatus;
use App\Models\Organization;
use App\Models\User;
use App\Models\Volunteer;
use App\Notifications\ApprovalRequestedNotification;
use Filament\Auth\Events\Registered;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;

function registerUser(string $email): User
{
    $user = User::factory()->create(['email' => $email]);

    event(new Registered($user));

    return $user;
}

it('creates an approved current volunteer without asking approvers when the email domain matches', function () {
    $this->freezeTime();
    Permission::create(['name' => 'Update:Volunteer', 'guard_name' => 'web']);
    User::factory()->create()->givePermissionTo('Update:Volunteer');
    $organization = Organization::factory()->create(['domain' => 'afges.org']);
    Notification::fake([ApprovalRequestedNotification::class]);

    $user = registerUser('jane@afges.org');

    $volunteer = Volunteer::where('user_id', $user->id)->sole();
    expect($volunteer->organization_id)->toBe($organization->id)
        ->and($volunteer->approval_status)->toBe(ApprovalStatus::Approved)
        ->and($volunteer->approved_at->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($volunteer->started_at->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($volunteer->isCurrentlyActive())->toBeTrue();
    Notification::assertNothingSent();
});

it('matches case-insensitively and tolerates a leading at sign', function () {
    $organization = Organization::factory()->create(['domain' => '@AFGES.org']);

    $user = registerUser('Jane@Afges.ORG');

    expect(Volunteer::where('user_id', $user->id)->sole()->organization_id)->toBe($organization->id);
});

it('creates nothing when no organization matches', function () {
    Organization::factory()->create(['domain' => 'afges.org']);
    Organization::factory()->create(['domain' => null]);

    registerUser('jane@example.com');

    $this->assertDatabaseCount('volunteers', 0);
});

it('does not match a domain that only shares a suffix', function () {
    Organization::factory()->create(['domain' => 'afges.org']);

    registerUser('jane@notafges.org');

    $this->assertDatabaseCount('volunteers', 0);
});

it('does not duplicate an existing volunteer, even a pending one', function () {
    $organization = Organization::factory()->create(['domain' => 'afges.org']);
    $user = User::factory()->create(['email' => 'jane@afges.org']);
    $pending = Volunteer::factory()->for($user)->for($organization)->create();

    event(new Registered($user));

    $this->assertDatabaseCount('volunteers', 1);
    expect($pending->refresh()->isPending())->toBeTrue();
});

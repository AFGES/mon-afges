<?php

declare(strict_types=1);

use App\Enums\ApprovalStatus;
use App\Models\Organization;
use App\Models\User;
use App\Models\Volunteer;
use App\Notifications\VolunteerApprovalRequested;
use Filament\Auth\Events\Registered;
use Illuminate\Support\Facades\Notification;

function registerUser(string $email): User
{
    $user = User::factory()->create(['email' => $email]);

    event(new Registered($user));

    return $user;
}

it('creates an approved current volunteer when the email domain matches', function () {
    Notification::fake();
    $organization = Organization::factory()->create(['domain' => 'afges.org']);

    $user = registerUser('jane@afges.org');

    $volunteer = Volunteer::where('user_id', $user->id)->sole();
    expect($volunteer->organization_id)->toBe($organization->id)
        ->and($volunteer->approval_status)->toBe(ApprovalStatus::Approved)
        ->and($volunteer->approved_at)->not->toBeNull()
        ->and($volunteer->isCurrentlyActive())->toBeTrue();

    Notification::assertNothingSent();
});

it('matches case-insensitively and tolerates a leading at sign', function () {
    $organization = Organization::factory()->create(['domain' => '@AFGES.org']);

    $user = registerUser('Jane@Afges.ORG');

    expect(Volunteer::where('user_id', $user->id)->sole()->organization_id)->toBe($organization->id);
});

it('does not notify approvers', function () {
    Notification::fake();
    Organization::factory()->create(['domain' => 'afges.org']);

    registerUser('jane@afges.org');

    Notification::assertNotSentTo(User::all(), VolunteerApprovalRequested::class);
});

it('creates nothing when no organization matches', function () {
    Organization::factory()->create(['domain' => 'afges.org']);
    Organization::factory()->create(['domain' => null]);

    $user = registerUser('jane@example.com');

    expect(Volunteer::withoutGlobalScopes()->where('user_id', $user->id)->exists())->toBeFalse();
});

it('does not match a domain that only shares a suffix', function () {
    Organization::factory()->create(['domain' => 'afges.org']);

    $user = registerUser('jane@notafges.org');

    expect(Volunteer::withoutGlobalScopes()->where('user_id', $user->id)->exists())->toBeFalse();
});

it('does not duplicate an existing volunteer, even a pending one', function () {
    $organization = Organization::factory()->create(['domain' => 'afges.org']);
    $user = User::factory()->create(['email' => 'jane@afges.org']);
    Volunteer::factory()->for($user)->for($organization)->create();

    event(new Registered($user));

    expect(Volunteer::withoutGlobalScopes()->where('user_id', $user->id)->count())->toBe(1);
});

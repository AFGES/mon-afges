<?php

declare(strict_types=1);

use App\Enums\ApprovalStatus;
use App\Models\Organization;
use App\Models\User;
use App\Models\Volunteer;

use function Pest\Laravel\actingAs;

test('volunteer can be approved', function () {
    $approver = User::factory()->create();
    $volunteer = Volunteer::withoutGlobalScopes()->create(Volunteer::factory()->make()->toArray());

    actingAs($approver);

    expect($volunteer->isPending())->toBeTrue();

    $volunteer->approve();

    $volunteer->refresh();

    expect($volunteer->isApproved())->toBeTrue();
    expect($volunteer->approved_at)->not->toBeNull();
    expect($volunteer->approved_by)->toBe($approver->id);
    expect($volunteer->rejection_reason)->toBeNull();
});

test('volunteer can be rejected', function () {
    $approver = User::factory()->create();
    $volunteer = Volunteer::withoutGlobalScopes()->create(Volunteer::factory()->make()->toArray());

    actingAs($approver);

    expect($volunteer->isPending())->toBeTrue();

    $reason = 'Not eligible';
    $volunteer->reject($reason);

    $volunteer->refresh();

    expect($volunteer->isRejected())->toBeTrue();
    expect($volunteer->approved_at)->not->toBeNull();
    expect($volunteer->approved_by)->toBe($approver->id);
    expect($volunteer->rejection_reason)->toBe($reason);
});

test('approved volunteer factory state creates approved volunteer', function () {
    $volunteer = Volunteer::factory()->approved()->create();

    expect($volunteer->isApproved())->toBeTrue();
    expect($volunteer->approved_at)->not->toBeNull();
    expect($volunteer->approved_by)->not->toBeNull();
});

test('rejected volunteer factory state creates rejected volunteer', function () {
    $volunteer = Volunteer::factory()->rejected()->create();

    expect($volunteer->isRejected())->toBeTrue();
    expect($volunteer->approved_at)->not->toBeNull();
    expect($volunteer->approved_by)->not->toBeNull();
    expect($volunteer->rejection_reason)->not->toBeNull();
});

test('new volunteer is pending by default', function () {
    $volunteer = Volunteer::withoutGlobalScopes()->create(Volunteer::factory()->make()->toArray());

    expect($volunteer->approval_status)->toBe(ApprovalStatus::Pending);
    expect($volunteer->isPending())->toBeTrue();
    expect($volunteer->isApproved())->toBeFalse();
    expect($volunteer->isRejected())->toBeFalse();
});

test('middleware only allows users with approved volunteers', function () {
    $user = User::factory()->create(['email' => 'test@afges.org']);
    $organization = Organization::factory()->create();

    // Create pending volunteer (bypass global scope to create pending)
    Volunteer::withoutGlobalScopes()->create(
        Volunteer::factory()->make([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ])->toArray()
    );

    actingAs($user);

    // Should be redirected since volunteer is not approved
    $response = $this->get('/');
    $response->assertRedirect(route('filament.root.pages.my-profile'));

    // Approve the volunteer (need to bypass scope to find it)
    $user->volunteers()->withoutGlobalScopes()->first()->approve();

    // Should be able to access dashboard now
    $response = $this->get('/');
    $response->assertSuccessful();
});

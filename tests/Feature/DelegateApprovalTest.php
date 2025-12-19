<?php

declare(strict_types=1);

use App\Enums\ApprovalStatus;
use App\Models\Delegate;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('delegate can be approved', function () {
    $approver = User::factory()->create();
    $delegate = Delegate::withoutGlobalScopes()->create(Delegate::factory()->make()->toArray());

    actingAs($approver);

    expect($delegate->isPending())->toBeTrue();

    $delegate->approve();

    $delegate->refresh();

    expect($delegate->isApproved())->toBeTrue();
    expect($delegate->approved_at)->not->toBeNull();
    expect($delegate->approved_by)->toBe($approver->id);
    expect($delegate->rejection_reason)->toBeNull();
});

test('delegate can be rejected', function () {
    $approver = User::factory()->create();
    $delegate = Delegate::withoutGlobalScopes()->create(Delegate::factory()->make()->toArray());

    actingAs($approver);

    expect($delegate->isPending())->toBeTrue();

    $reason = 'Not eligible';
    $delegate->reject($reason);

    $delegate->refresh();

    expect($delegate->isRejected())->toBeTrue();
    expect($delegate->approved_at)->not->toBeNull();
    expect($delegate->approved_by)->toBe($approver->id);
    expect($delegate->rejection_reason)->toBe($reason);
});

test('new delegate is pending by default', function () {
    $delegate = Delegate::withoutGlobalScopes()->create(Delegate::factory()->make()->toArray());

    expect($delegate->status)->toBe(ApprovalStatus::Pending);
    expect($delegate->isPending())->toBeTrue();
    expect($delegate->isApproved())->toBeFalse();
    expect($delegate->isRejected())->toBeFalse();
});

test('approved delegate factory state creates approved delegate', function () {
    $delegate = Delegate::factory()->approved()->create();

    expect($delegate->isApproved())->toBeTrue();
    expect($delegate->approved_at)->not->toBeNull();
    expect($delegate->approved_by)->not->toBeNull();
});

test('rejected delegate factory state creates rejected delegate', function () {
    $delegate = Delegate::factory()->rejected()->create();

    expect($delegate->isRejected())->toBeTrue();
    expect($delegate->approved_at)->not->toBeNull();
    expect($delegate->approved_by)->not->toBeNull();
    expect($delegate->rejection_reason)->not->toBeNull();
});

test('approved scope filters only approved delegates', function () {
    // Create delegates with different statuses (bypass global scope)
    Delegate::withoutGlobalScopes()->create(Delegate::factory()->make()->toArray()); // Pending
    Delegate::factory()->approved()->create(); // Approved
    Delegate::factory()->rejected()->create(); // Rejected

    // Query with ApprovedScope active (default for Delegate model)
    $approvedDelegates = Delegate::all();

    expect($approvedDelegates)->toHaveCount(1);
    expect($approvedDelegates->first()->isApproved())->toBeTrue();
});

test('can query pending delegates without global scope', function () {
    // Create delegates with different statuses
    Delegate::withoutGlobalScopes()->create(Delegate::factory()->make()->toArray()); // Pending
    Delegate::withoutGlobalScopes()->create(Delegate::factory()->make()->toArray()); // Pending
    Delegate::factory()->approved()->create(); // Approved

    $pendingDelegates = Delegate::withoutGlobalScopes()
        ->where('status', ApprovalStatus::Pending)
        ->get();

    expect($pendingDelegates)->toHaveCount(2);
    expect($pendingDelegates->first()->isPending())->toBeTrue();
});

test('cannot approve an already approved delegate', function () {
    $delegate = Delegate::factory()->approved()->create();

    expect($delegate->isApproved())->toBeTrue();

    $result = $delegate->approve();

    expect($result)->toBeFalse();
});

test('cannot reject an already rejected delegate', function () {
    $delegate = Delegate::factory()->rejected()->create();

    expect($delegate->isRejected())->toBeTrue();

    $result = $delegate->reject('Another reason');

    expect($result)->toBeFalse();
});

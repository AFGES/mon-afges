<?php

declare(strict_types=1);

use App\Enums\ApprovalStatus;
use App\Models\Delegate;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('can approve a delegate and sets approval data correctly', function (): void {
    $approver = User::factory()->create();
    $delegate = Delegate::factory()->create([
        'status' => ApprovalStatus::Pending,
    ]);

    actingAs($approver);

    $delegate->approve();

    expect($delegate->refresh())
        ->isApproved()->toBeTrue()
        ->approved_at->not->toBeNull()
        ->approved_by->toBe($approver->id)
        ->rejection_reason->toBeNull();
});

it('can reject a delegate with a reason', function (): void {
    $rejecter = User::factory()->create();
    $delegate = Delegate::factory()->create([
        'status' => ApprovalStatus::Pending,
    ]);
    $rejectionReason = 'Documents incomplets';

    actingAs($rejecter);

    $delegate->reject($rejectionReason);

    expect($delegate->refresh())
        ->isRejected()->toBeTrue()
        ->approved_at->not->toBeNull()
        ->approved_by->toBe($rejecter->id)
        ->rejection_reason->toBe($rejectionReason);
});

it('cannot approve an already approved delegate', function (): void {
    $delegate = Delegate::factory()->create([
        'status' => ApprovalStatus::Approved,
        'approved_at' => now()->subDay(),
        'approved_by' => User::factory()->create()->id,
    ]);

    $result = $delegate->approve();

    expect($result)->toBeFalse();
});

it('cannot reject an already rejected delegate', function (): void {
    $delegate = Delegate::factory()->create([
        'status' => ApprovalStatus::Rejected,
        'approved_at' => now()->subDay(),
        'approved_by' => User::factory()->create()->id,
        'rejection_reason' => 'Original reason',
    ]);

    $result = $delegate->reject('New reason');

    expect($result)->toBeFalse();
    expect($delegate->refresh()->rejection_reason)->toBe('Original reason');
});

it('clears rejection reason when approving a delegate', function (): void {
    $delegate = Delegate::factory()->create([
        'status' => ApprovalStatus::Pending,
    ]);

    // First reject
    $delegate->reject('Test reason');
    expect($delegate->refresh()->rejection_reason)->toBe('Test reason');

    // Then approve (simulating a status change)
    $delegate->update(['status' => ApprovalStatus::Pending]);
    $delegate->approve();

    expect($delegate->refresh()->rejection_reason)->toBeNull();
});

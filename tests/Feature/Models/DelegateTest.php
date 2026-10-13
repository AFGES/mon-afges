<?php

declare(strict_types=1);

use App\Models\CollegeOrganization;
use App\Models\Delegate;
use App\Models\Organization;
use App\Models\User;

test('delegate has started at date', function () {
    $delegate = Delegate::factory()->create([
        'started_at' => '2025-01-01',
    ]);

    expect($delegate->started_at->format('Y-m-d'))->toBe('2025-01-01');
});

test('delegate has ended at date', function () {
    $delegate = Delegate::factory()->create([
        'started_at' => '2025-01-01',
        'ended_at' => '2025-12-31',
    ]);

    expect($delegate->ended_at->format('Y-m-d'))->toBe('2025-12-31');
});

test('delegate can have null ended at date', function () {
    $delegate = Delegate::factory()->create([
        'started_at' => '2025-01-01',
        'ended_at' => null,
    ]);

    expect($delegate->ended_at)->toBeNull();
});

test('delegate belongs to user', function () {
    $user = User::factory()->create(['name' => 'John Doe']);
    $delegate = Delegate::factory()->create(['user_id' => $user->id]);

    expect($delegate->user->name)->toBe('John Doe');
});

test('delegate belongs to organization', function () {
    $organization = Organization::factory()->create(['short_name' => 'TEST']);
    $delegate = Delegate::factory()->create(['organization_id' => $organization->id]);

    expect($delegate->organization->short_name)->toBe('TEST');
});

test('delegate belongs to seat', function () {
    $collegeOrg = CollegeOrganization::factory()->withSeats(1)->create();
    $seat = $collegeOrg->seatRecords->first();

    $delegate = Delegate::factory()->create(['seat_id' => $seat->id]);

    expect($delegate->seat->id)->toBe($seat->id);
});

test('delegate can be soft deleted', function () {
    $delegate = Delegate::factory()->create();

    expect(Delegate::withoutGlobalScopes()->count())->toBe(1);

    $delegate->delete();

    // After soft delete, the record is still there but with deleted_at set
    expect($delegate->trashed())->toBeTrue()
        ->and(Delegate::withoutGlobalScopes()->withTrashed()->count())->toBe(1);
});

test('delegate can be restored after soft delete', function () {
    $delegate = Delegate::factory()->create();

    $delegate->delete();
    expect($delegate->trashed())->toBeTrue();

    $delegate->restore();
    expect($delegate->trashed())->toBeFalse();
});

test('delegate is scoped by currently scope', function () {
    // Create delegate that started in the past and has no end date (currently active)
    $activeDelegate = Delegate::factory()->create([
        'started_at' => now()->subDays(10),
        'ended_at' => null,
    ]);

    // Create delegate that ended in the past (not currently active)
    $pastDelegate = Delegate::factory()->create([
        'started_at' => now()->subDays(20),
        'ended_at' => now()->subDays(5),
    ]);

    // Without removing the scope, only active delegate should be returned
    $delegates = Delegate::withoutGlobalScopes()->get();

    expect($delegates)->toHaveCount(2);
});

test('delegate keeps date casts declared on the model over trait defaults', function () {
    $casts = (new Delegate)->getCasts();

    expect($casts['started_at'])->toBe('date')
        ->and($casts['ended_at'])->toBe('date');
});

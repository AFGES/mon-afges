<?php

declare(strict_types=1);

use App\Models\Delegate;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('delegate policy allows user to terminate their own approved active mandate', function () {
    $user = User::factory()->create();
    $delegate = Delegate::factory()->approved()->create([
        'user_id' => $user->id,
        'ended_at' => null,
    ]);

    expect($user->can('terminate', $delegate))->toBeTrue();
});

test('delegate policy denies terminating another users mandate', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $delegate = Delegate::factory()->approved()->create([
        'user_id' => $otherUser->id,
        'ended_at' => null,
    ]);

    expect($user->can('terminate', $delegate))->toBeFalse();
});

test('delegate policy denies terminating pending mandate', function () {
    $user = User::factory()->create();
    $delegate = Delegate::factory()->pending()->create([
        'user_id' => $user->id,
        'ended_at' => null,
    ]);

    expect($user->can('terminate', $delegate))->toBeFalse();
});

test('delegate policy denies terminating already ended mandate', function () {
    $user = User::factory()->create();
    $delegate = Delegate::factory()->approved()->create([
        'user_id' => $user->id,
        'ended_at' => now()->subDays(5),
    ]);

    expect($user->can('terminate', $delegate))->toBeFalse();
});

test('delegate policy denies terminating rejected mandate', function () {
    $user = User::factory()->create();
    $delegate = Delegate::factory()->rejected()->create([
        'user_id' => $user->id,
        'ended_at' => null,
    ]);

    expect($user->can('terminate', $delegate))->toBeFalse();
});

test('delegate policy allows update only for users holding the update permission', function () {
    Permission::create(['name' => 'Update:Delegate', 'guard_name' => 'web']);
    $approver = User::factory()->create()->givePermissionTo('Update:Delegate');
    $user = User::factory()->create();
    $delegate = Delegate::factory()->create();

    expect($approver->can('update', $delegate))->toBeTrue()
        ->and($user->can('update', $delegate))->toBeFalse();
});

test('delegate policy lets a super admin terminate an active mandate of someone else', function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    $superAdmin = User::factory()->create()->assignRole('super_admin');
    $delegate = Delegate::factory()->approved()->create(['ended_at' => null]);

    expect($superAdmin->can('terminate', $delegate))->toBeTrue()
        ->and($superAdmin->can('update', $delegate))->toBeTrue();
});

test('delegate policy denies a super admin terminating an already ended mandate', function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    $superAdmin = User::factory()->create()->assignRole('super_admin');
    $delegate = Delegate::factory()->approved()->create(['ended_at' => now()->subDays(5)]);

    expect($superAdmin->can('terminate', $delegate))->toBeFalse();
});

<?php

declare(strict_types=1);

use App\Models\Delegate;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('allows a user to terminate their own approved active delegate mandate', function () {
    $user = User::factory()->create();
    $delegate = Delegate::factory()->approved()->create([
        'user_id' => $user->id,
        'ended_at' => null,
    ]);

    expect($user->can('terminate', $delegate))->toBeTrue();
});

test('forbids terminating the delegate mandate of another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $delegate = Delegate::factory()->approved()->create([
        'user_id' => $otherUser->id,
        'ended_at' => null,
    ]);

    expect($user->can('terminate', $delegate))->toBeFalse();
});

test('forbids terminating a pending delegate mandate', function () {
    $user = User::factory()->create();
    $delegate = Delegate::factory()->pending()->create([
        'user_id' => $user->id,
        'ended_at' => null,
    ]);

    expect($user->can('terminate', $delegate))->toBeFalse();
});

test('forbids terminating an already ended delegate mandate', function () {
    $user = User::factory()->create();
    $delegate = Delegate::factory()->approved()->create([
        'user_id' => $user->id,
        'ended_at' => now()->subDays(5),
    ]);

    expect($user->can('terminate', $delegate))->toBeFalse();
});

test('forbids terminating a rejected delegate mandate', function () {
    $user = User::factory()->create();
    $delegate = Delegate::factory()->rejected()->create([
        'user_id' => $user->id,
        'ended_at' => null,
    ]);

    expect($user->can('terminate', $delegate))->toBeFalse();
});

test('allows updating a delegate only for users holding the update permission', function () {
    Permission::create(['name' => 'Update:Delegate', 'guard_name' => 'web']);
    $approver = User::factory()->create()->givePermissionTo('Update:Delegate');
    $user = User::factory()->create();
    $delegate = Delegate::factory()->create();

    expect($approver->can('update', $delegate))->toBeTrue()
        ->and($user->can('update', $delegate))->toBeFalse();
});

test('allows a super admin to terminate the active delegate mandate of another user', function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    $superAdmin = User::factory()->create()->assignRole('super_admin');
    $delegate = Delegate::factory()->approved()->create(['ended_at' => null]);

    expect($superAdmin->can('terminate', $delegate))->toBeTrue()
        ->and($superAdmin->can('update', $delegate))->toBeTrue();
});

test('forbids a super admin from terminating an already ended delegate mandate', function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    $superAdmin = User::factory()->create()->assignRole('super_admin');
    $delegate = Delegate::factory()->approved()->create(['ended_at' => now()->subDays(5)]);

    expect($superAdmin->can('terminate', $delegate))->toBeFalse();
});

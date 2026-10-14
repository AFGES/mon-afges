<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Volunteer;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('volunteer policy allows user to terminate their own approved active mandate', function () {
    $user = User::factory()->create();
    $volunteer = Volunteer::factory()->approved()->create([
        'user_id' => $user->id,
        'ended_at' => null,
    ]);

    expect($user->can('terminate', $volunteer))->toBeTrue();
});

test('volunteer policy denies terminating another users mandate', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $volunteer = Volunteer::factory()->approved()->create([
        'user_id' => $otherUser->id,
        'ended_at' => null,
    ]);

    expect($user->can('terminate', $volunteer))->toBeFalse();
});

test('volunteer policy denies terminating pending mandate', function () {
    $user = User::factory()->create();
    $volunteer = Volunteer::factory()->pending()->create([
        'user_id' => $user->id,
        'ended_at' => null,
    ]);

    expect($user->can('terminate', $volunteer))->toBeFalse();
});

test('volunteer policy denies terminating already ended mandate', function () {
    $user = User::factory()->create();
    $volunteer = Volunteer::factory()->approved()->create([
        'user_id' => $user->id,
        'ended_at' => now()->subDays(5),
    ]);

    expect($user->can('terminate', $volunteer))->toBeFalse();
});

test('volunteer policy denies terminating rejected mandate', function () {
    $user = User::factory()->create();
    $volunteer = Volunteer::factory()->rejected()->create([
        'user_id' => $user->id,
        'ended_at' => null,
    ]);

    expect($user->can('terminate', $volunteer))->toBeFalse();
});

test('volunteer policy allows update only for users holding the update permission', function () {
    Permission::create(['name' => 'Update:Volunteer', 'guard_name' => 'web']);
    $approver = User::factory()->create()->givePermissionTo('Update:Volunteer');
    $user = User::factory()->create();
    $volunteer = Volunteer::factory()->create();

    expect($approver->can('update', $volunteer))->toBeTrue()
        ->and($user->can('update', $volunteer))->toBeFalse();
});

test('volunteer policy lets a super admin terminate an active mandate of someone else', function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    $superAdmin = User::factory()->create()->assignRole('super_admin');
    $volunteer = Volunteer::factory()->approved()->create(['ended_at' => null]);

    expect($superAdmin->can('terminate', $volunteer))->toBeTrue()
        ->and($superAdmin->can('update', $volunteer))->toBeTrue();
});

test('volunteer policy denies a super admin terminating an already ended mandate', function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    $superAdmin = User::factory()->create()->assignRole('super_admin');
    $volunteer = Volunteer::factory()->approved()->create(['ended_at' => now()->subDays(5)]);

    expect($superAdmin->can('terminate', $volunteer))->toBeFalse();
});

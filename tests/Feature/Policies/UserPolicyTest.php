<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);

    foreach (['Update:User', 'Delete:User', 'ForceDelete:User'] as $permission) {
        Permission::create(['name' => $permission, 'guard_name' => 'web']);
    }

    $this->manager = User::factory()->create()->givePermissionTo(['Update:User', 'Delete:User', 'ForceDelete:User']);
    $this->superAdmin = User::factory()->create()->assignRole('super_admin');
    $this->regularUser = User::factory()->create();
});

test('forbids non super admins from modifying a super admin', function (string $ability) {
    expect($this->manager->can($ability, $this->superAdmin))->toBeFalse();
})->with(['update', 'delete', 'forceDelete']);

test('allows managers holding the permission to modify a regular user', function (string $ability) {
    expect($this->manager->can($ability, $this->regularUser))->toBeTrue();
})->with(['update', 'delete', 'forceDelete']);

test('lets a super admin modify another super admin through the gate bypass', function () {
    $otherSuperAdmin = User::factory()->create()->assignRole('super_admin');

    expect($this->superAdmin->can('update', $otherSuperAdmin))->toBeTrue();
});

test('forbids non super admins from restoring or replicating a super admin', function (string $ability) {
    Permission::create(['name' => 'Restore:User', 'guard_name' => 'web']);
    Permission::create(['name' => 'Replicate:User', 'guard_name' => 'web']);
    $this->manager->givePermissionTo(['Restore:User', 'Replicate:User']);

    expect($this->manager->can($ability, $this->superAdmin))->toBeFalse()
        ->and($this->manager->can($ability, $this->regularUser))->toBeTrue();
})->with(['restore', 'replicate']);

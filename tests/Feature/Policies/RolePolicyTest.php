<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->superAdminRole = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    $this->editorRole = Role::create(['name' => 'editor', 'guard_name' => 'web']);

    $permissions = ['Update:Role', 'Delete:Role', 'ForceDelete:Role', 'Replicate:Role'];

    foreach ($permissions as $permission) {
        Permission::create(['name' => $permission, 'guard_name' => 'web']);
    }

    $this->manager = User::factory()->create()->givePermissionTo($permissions);
});

test('forbids non super admins from modifying the super admin role', function (string $ability) {
    expect($this->manager->can($ability, $this->superAdminRole))->toBeFalse();
})->with(['update', 'delete', 'forceDelete', 'replicate']);

test('allows holders of the permission to modify another role', function (string $ability) {
    expect($this->manager->can($ability, $this->editorRole))->toBeTrue();
})->with(['update', 'delete', 'forceDelete', 'replicate']);

test('lets a super admin modify the super admin role through the gate bypass', function () {
    $superAdmin = User::factory()->create()->assignRole($this->superAdminRole);

    expect($superAdmin->can('update', $this->superAdminRole))->toBeTrue();
});

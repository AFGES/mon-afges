<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\RoleAssignmentService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->service = new RoleAssignmentService;
    $this->superAdminRole = Role::create(['name' => User::SUPER_ADMIN_ROLE, 'guard_name' => 'web']);
    $this->held = Permission::create(['name' => 'View:User', 'guard_name' => 'web']);
    $this->notHeld = Permission::create(['name' => 'Delete:User', 'guard_name' => 'web']);

    $this->safeRole = Role::create(['name' => 'safe', 'guard_name' => 'web'])->givePermissionTo($this->held);
    $this->privilegedRole = Role::create(['name' => 'privileged', 'guard_name' => 'web'])->givePermissionTo($this->held, $this->notHeld);

    $this->actor = User::factory()->create()->givePermissionTo($this->held);
    $this->superAdmin = User::factory()->create()->assignRole(User::SUPER_ADMIN_ROLE);
});

it('only offers roles whose permissions the actor holds and never super admin', function () {
    expect($this->service->assignableRoleIds($this->actor)->all())->toBe([$this->safeRole->id])
        ->and($this->service->canAssignAnyRole($this->actor))->toBeFalse();
});

it('offers no roles to a guest', function () {
    expect($this->service->assignableRoleIds(null))->toBeEmpty()
        ->and($this->service->canAssignAnyRole(null))->toBeFalse();
});

it('lets a super admin assign any role', function () {
    $target = User::factory()->create();

    $this->service->syncRoles($this->superAdmin, $target, [$this->superAdminRole->id, $this->privilegedRole->id]);

    expect($this->service->canAssignAnyRole($this->superAdmin))->toBeTrue()
        ->and($target->fresh()->roles->pluck('name')->sort()->values()->all())->toBe(['privileged', User::SUPER_ADMIN_ROLE]);
});

it('does not let a non super admin grant super admin or privileged roles', function () {
    $target = User::factory()->create();

    $this->service->syncRoles($this->actor, $target, [
        $this->superAdminRole->id,
        $this->privilegedRole->id,
        $this->safeRole->id,
    ]);

    expect($target->fresh()->roles->pluck('name')->all())->toBe(['safe']);
});

it('preserves roles the actor cannot assign when syncing', function () {
    $target = User::factory()->create()->assignRole($this->privilegedRole, $this->safeRole);

    $this->service->syncRoles($this->actor, $target, []);

    expect($target->fresh()->roles->pluck('name')->all())->toBe(['privileged']);
});

<?php

declare(strict_types=1);

use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Models\User;
use Filament\Actions\DeleteBulkAction;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertModelExists;
use function Pest\Laravel\assertModelMissing;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->superAdminRole = Role::create(['name' => User::SUPER_ADMIN_ROLE, 'guard_name' => 'web']);
    $this->editorRole = Role::create(['name' => 'editor', 'guard_name' => 'web']);

    $permissions = ['ViewAny:Role', 'View:Role', 'Update:Role', 'Delete:Role'];

    foreach ($permissions as $permission) {
        Permission::create(['name' => $permission, 'guard_name' => 'web']);
    }

    $this->manager = User::factory()->create()->givePermissionTo($permissions);
});

it('hides the super admin role from non super admins', function () {
    actingAs($this->manager);

    livewire(ListRoles::class)
        ->assertCanSeeTableRecords([$this->editorRole])
        ->assertCanNotSeeTableRecords([$this->superAdminRole]);
});

it('keeps the super admin role when a non super admin bulk deletes it', function () {
    actingAs($this->manager);

    livewire(ListRoles::class)
        ->callTableBulkAction(DeleteBulkAction::class, [$this->superAdminRole, $this->editorRole]);

    assertModelExists($this->superAdminRole);
    assertModelMissing($this->editorRole);
});

it('forbids non super admins from opening the super admin role', function () {
    actingAs($this->manager);

    livewire(EditRole::class, ['record' => $this->superAdminRole->getRouteKey()])
        ->assertNotFound();
});

it('shows the super admin role to super admins', function () {
    actingAs(User::factory()->create()->assignRole($this->superAdminRole));

    livewire(ListRoles::class)
        ->assertCanSeeTableRecords([$this->superAdminRole, $this->editorRole]);
});

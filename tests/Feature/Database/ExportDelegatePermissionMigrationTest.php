<?php

declare(strict_types=1);

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function exportDelegatePermissionMigration(): object
{
    return require database_path('migrations/2026_10_19_200000_create_export_delegate_permission.php');
}

it('grants the export permission to the roles that can create delegates only', function () {
    $creator = Role::findOrCreate('delegate_manager', 'web')->givePermissionTo(Permission::findOrCreate('Create:Delegate', 'web'));
    $viewer = Role::findOrCreate('delegate_viewer', 'web')->givePermissionTo(Permission::findOrCreate('ViewAny:Delegate', 'web'));

    exportDelegatePermissionMigration()->up();
    exportDelegatePermissionMigration()->up();

    expect($creator->fresh()->hasPermissionTo('Export:Delegate'))->toBeTrue()
        ->and($viewer->fresh()->hasPermissionTo('Export:Delegate'))->toBeFalse();
});

it('creates the permission when no role can create delegates and removes it when rolled back', function () {
    exportDelegatePermissionMigration()->up();

    expect(Permission::query()->where('name', 'Export:Delegate')->exists())->toBeTrue();

    exportDelegatePermissionMigration()->down();

    expect(Permission::query()->where('name', 'Export:Delegate')->exists())->toBeFalse();
});

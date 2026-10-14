<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function proxyPermissionsMigration(): object
{
    return require database_path('migrations/2026_10_14_145328_create_proxy_permissions.php');
}

it('creates every permission required by ProxyPolicy', function () {
    Permission::query()->where('name', 'like', '%:Proxy')->delete();

    proxyPermissionsMigration()->up();

    expect(Permission::query()->where('guard_name', 'web')->where('name', 'like', '%:Proxy')->pluck('name')->all())
        ->toEqualCanonicalizing([
            'ViewAny:Proxy', 'View:Proxy', 'Create:Proxy', 'Update:Proxy', 'Delete:Proxy',
            'Restore:Proxy', 'ForceDelete:Proxy', 'ForceDeleteAny:Proxy', 'RestoreAny:Proxy',
            'Replicate:Proxy', 'Reorder:Proxy',
        ]);
});

it('does not assign the permissions to any role and is idempotent', function () {
    Role::findOrCreate('super_admin', 'web');

    proxyPermissionsMigration()->up();
    proxyPermissionsMigration()->up();

    expect(Permission::query()->where('name', 'like', '%:Proxy')->count())->toBe(11)
        ->and(DB::table(config('permission.table_names.role_has_permissions'))->count())->toBe(0);
});

it('removes the permissions when rolled back', function () {
    proxyPermissionsMigration()->up();
    proxyPermissionsMigration()->down();

    expect(Permission::query()->where('name', 'like', '%:Proxy')->exists())->toBeFalse();
});

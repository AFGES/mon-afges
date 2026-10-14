<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /** @var list<string> */
    private array $permissionNames = [
        'ViewAny:Proxy',
        'View:Proxy',
        'Create:Proxy',
        'Update:Proxy',
        'Delete:Proxy',
        'Restore:Proxy',
        'ForceDelete:Proxy',
        'ForceDeleteAny:Proxy',
        'RestoreAny:Proxy',
        'Replicate:Proxy',
        'Reorder:Proxy',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissionNames as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $this->permissionNames)
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

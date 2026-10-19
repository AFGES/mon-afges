<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const string EXPORT_PERMISSION = 'Export:Delegate';

    private const string CREATE_PERMISSION = 'Create:Delegate';

    /**
     * Roles allowed to create delegates keep access to the export, which used to be gated by that permission.
     */
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $exportPermission = Permission::findOrCreate(self::EXPORT_PERMISSION, 'web');

        Permission::query()
            ->where('guard_name', 'web')
            ->where('name', self::CREATE_PERMISSION)
            ->first()
            ?->roles
            ->each(fn ($role) => $role->givePermissionTo($exportPermission));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()
            ->where('guard_name', 'web')
            ->where('name', self::EXPORT_PERMISSION)
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

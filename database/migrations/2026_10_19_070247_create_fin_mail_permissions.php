<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /** @var list<string> */
    private array $permissionNames = [
        'ViewAny:EmailTemplate',
        'View:EmailTemplate',
        'Update:EmailTemplate',
        'Preview:EmailTemplate',
        'SendTest:EmailTemplate',
        'ViewAny:SentEmail',
        'View:SentEmail',
        'Resend:SentEmail',
        'View:ManageGeneralSettings',
        'View:ManageBrandingSettings',
        'View:ManageLoggingSettings',
        'View:ManageAttachmentSettings',
        'View:ManageAuthEmailSettings',
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

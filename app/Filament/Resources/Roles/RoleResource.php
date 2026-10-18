<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\Pages\ViewRole;
use App\Models\Scopes\ExcludeSuperAdminRoleScope;
use App\Services\RoleAssignmentService;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource as ShieldRoleResource;
use Illuminate\Database\Eloquent\Builder;

/**
 * Hides the super admin role from non-super-admins, so it can never be
 * listed, opened or bulk deleted by them. Shield skips registering its own
 * resource when a class named `RoleResource` is already on the panel.
 */
final class RoleResource extends ShieldRoleResource
{
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(
                ! resolve(RoleAssignmentService::class)->isSuperAdmin(auth()->user()),
                fn (Builder $query): Builder => $query->withGlobalScope(ExcludeSuperAdminRoleScope::class, new ExcludeSuperAdminRoleScope),
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'view' => ViewRole::route('/{record}'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}

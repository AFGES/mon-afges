<?php

declare(strict_types=1);

namespace App\Policies;

use App\Policies\Contracts\RestrictsSuperAdmin;
use FinityLabs\FinMail\Models\EmailTheme;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Themes are disabled in the panel (FinMailPlugin::enableThemes(false)); this policy keeps them
 * closed should the resource be reached anyway.
 */
final class EmailThemePolicy implements RestrictsSuperAdmin
{
    use HandlesAuthorization;

    /**
     * @return list<string>
     */
    public function superAdminRestrictedAbilities(): array
    {
        return [
            'viewAny', 'view', 'create', 'update', 'delete', 'deleteAny', 'restore', 'restoreAny',
            'forceDelete', 'forceDeleteAny', 'replicate', 'reorder',
        ];
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function view(AuthUser $authUser, EmailTheme $emailTheme): bool
    {
        return false;
    }

    public function create(AuthUser $authUser): bool
    {
        return false;
    }

    public function update(AuthUser $authUser, EmailTheme $emailTheme): bool
    {
        return false;
    }

    public function delete(AuthUser $authUser, EmailTheme $emailTheme): bool
    {
        return false;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restore(AuthUser $authUser, EmailTheme $emailTheme): bool
    {
        return false;
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function forceDelete(AuthUser $authUser, EmailTheme $emailTheme): bool
    {
        return false;
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function replicate(AuthUser $authUser, EmailTheme $emailTheme): bool
    {
        return false;
    }

    public function reorder(AuthUser $authUser): bool
    {
        return false;
    }
}

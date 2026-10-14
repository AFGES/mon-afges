<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Delegate;
use App\Models\User;
use App\Policies\Contracts\RestrictsSuperAdmin;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class DelegatePolicy implements RestrictsSuperAdmin
{
    use HandlesAuthorization;

    /**
     * @return list<string>
     */
    public function superAdminRestrictedAbilities(): array
    {
        return ['terminate'];
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Delegate');
    }

    public function view(AuthUser $authUser, Delegate $delegate): bool
    {
        return $authUser->can('View:Delegate');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Delegate');
    }

    public function update(AuthUser $authUser, Delegate $delegate): bool
    {
        return $authUser->can('Update:Delegate');
    }

    public function delete(AuthUser $authUser, Delegate $delegate): bool
    {
        return $authUser->can('Delete:Delegate');
    }

    public function restore(AuthUser $authUser, Delegate $delegate): bool
    {
        return $authUser->can('Restore:Delegate');
    }

    public function forceDelete(AuthUser $authUser, Delegate $delegate): bool
    {
        return $authUser->can('ForceDelete:Delegate');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Delegate');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Delegate');
    }

    public function replicate(AuthUser $authUser, Delegate $delegate): bool
    {
        return $authUser->can('Replicate:Delegate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Delegate');
    }

    public function terminate(AuthUser $authUser, Delegate $delegate): bool
    {
        $canManageMandate = $delegate->user_id === $authUser->id || ($authUser instanceof User && $authUser->isSuperAdmin());

        return $canManageMandate
            && $delegate->isApproved()
            && $delegate->ended_at === null;
    }
}

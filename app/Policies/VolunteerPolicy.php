<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Volunteer;
use App\Policies\Contracts\RestrictsSuperAdmin;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class VolunteerPolicy implements RestrictsSuperAdmin
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
        return $authUser->can('ViewAny:Volunteer');
    }

    public function view(AuthUser $authUser, Volunteer $volunteer): bool
    {
        return $authUser->can('View:Volunteer');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Volunteer');
    }

    public function update(AuthUser $authUser, Volunteer $volunteer): bool
    {
        return $authUser->can('Update:Volunteer');
    }

    public function delete(AuthUser $authUser, Volunteer $volunteer): bool
    {
        return $authUser->can('Delete:Volunteer');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:Volunteer');
    }

    public function restore(AuthUser $authUser, Volunteer $volunteer): bool
    {
        return $authUser->can('Restore:Volunteer');
    }

    public function forceDelete(AuthUser $authUser, Volunteer $volunteer): bool
    {
        return $authUser->can('ForceDelete:Volunteer');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Volunteer');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Volunteer');
    }

    public function replicate(AuthUser $authUser, Volunteer $volunteer): bool
    {
        return $authUser->can('Replicate:Volunteer');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Volunteer');
    }

    public function terminate(AuthUser $authUser, Volunteer $volunteer): bool
    {
        $canManageMandate = $volunteer->user_id === $authUser->id || ($authUser instanceof User && $authUser->isSuperAdmin());

        return $canManageMandate
            && $volunteer->isApproved()
            && $volunteer->ended_at === null;
    }
}

<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Volunteer;
use Illuminate\Auth\Access\HandlesAuthorization;

class VolunteerPolicy
{
    use HandlesAuthorization;
    
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

}
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DemocraticTimeSession;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class DemocraticTimeSessionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DemocraticTimeSession');
    }

    public function view(AuthUser $authUser, DemocraticTimeSession $democraticTimeSession): bool
    {
        return $authUser->can('View:DemocraticTimeSession');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DemocraticTimeSession');
    }

    public function update(AuthUser $authUser, DemocraticTimeSession $democraticTimeSession): bool
    {
        return $authUser->can('Update:DemocraticTimeSession');
    }

    public function delete(AuthUser $authUser, DemocraticTimeSession $democraticTimeSession): bool
    {
        return $authUser->can('Delete:DemocraticTimeSession');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:DemocraticTimeSession');
    }

    public function restore(AuthUser $authUser, DemocraticTimeSession $democraticTimeSession): bool
    {
        return $authUser->can('Restore:DemocraticTimeSession');
    }

    public function forceDelete(AuthUser $authUser, DemocraticTimeSession $democraticTimeSession): bool
    {
        return $authUser->can('ForceDelete:DemocraticTimeSession');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DemocraticTimeSession');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DemocraticTimeSession');
    }

    public function replicate(AuthUser $authUser, DemocraticTimeSession $democraticTimeSession): bool
    {
        return $authUser->can('Replicate:DemocraticTimeSession');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DemocraticTimeSession');
    }
}

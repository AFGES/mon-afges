<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DemocraticTimeSession;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DemocraticTimeSessionPolicy
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

    public function restore(AuthUser $authUser, DemocraticTimeSession $democraticTimeSession): bool
    {
        return $authUser->can('Restore:DemocraticTimeSession');
    }

    public function forceDelete(AuthUser $authUser, DemocraticTimeSession $democraticTimeSession): bool
    {
        return $authUser->can('ForceDelete:DemocraticTimeSession');
    }
}

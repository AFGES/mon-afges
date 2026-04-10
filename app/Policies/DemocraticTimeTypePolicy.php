<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DemocraticTimeType;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class DemocraticTimeTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DemocraticTimeType');
    }

    public function view(AuthUser $authUser, DemocraticTimeType $democraticTimeType): bool
    {
        return $authUser->can('View:DemocraticTimeType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DemocraticTimeType');
    }

    public function update(AuthUser $authUser, DemocraticTimeType $democraticTimeType): bool
    {
        return $authUser->can('Update:DemocraticTimeType');
    }

    public function delete(AuthUser $authUser, DemocraticTimeType $democraticTimeType): bool
    {
        return $authUser->can('Delete:DemocraticTimeType');
    }

    public function restore(AuthUser $authUser, DemocraticTimeType $democraticTimeType): bool
    {
        return $authUser->can('Restore:DemocraticTimeType');
    }

    public function forceDelete(AuthUser $authUser, DemocraticTimeType $democraticTimeType): bool
    {
        return $authUser->can('ForceDelete:DemocraticTimeType');
    }
}

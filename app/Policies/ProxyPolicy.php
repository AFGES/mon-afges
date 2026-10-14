<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Proxy;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class ProxyPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Proxy');
    }

    public function view(AuthUser $authUser, Proxy $proxy): bool
    {
        return $authUser->can('View:Proxy');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Proxy');
    }

    public function update(AuthUser $authUser, Proxy $proxy): bool
    {
        return $authUser->can('Update:Proxy');
    }

    public function delete(AuthUser $authUser, Proxy $proxy): bool
    {
        return $authUser->can('Delete:Proxy');
    }

    public function restore(AuthUser $authUser, Proxy $proxy): bool
    {
        return $authUser->can('Restore:Proxy');
    }

    public function forceDelete(AuthUser $authUser, Proxy $proxy): bool
    {
        return $authUser->can('ForceDelete:Proxy');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Proxy');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Proxy');
    }

    public function replicate(AuthUser $authUser, Proxy $proxy): bool
    {
        return $authUser->can('Replicate:Proxy');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Proxy');
    }
}

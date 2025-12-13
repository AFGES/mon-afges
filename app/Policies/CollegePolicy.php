<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\College;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CollegePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:College');
    }

    public function view(AuthUser $authUser, College $college): bool
    {
        return $authUser->can('View:College');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:College');
    }

    public function update(AuthUser $authUser, College $college): bool
    {
        return $authUser->can('Update:College');
    }

    public function delete(AuthUser $authUser, College $college): bool
    {
        return $authUser->can('Delete:College');
    }

    public function restore(AuthUser $authUser, College $college): bool
    {
        return $authUser->can('Restore:College');
    }

    public function forceDelete(AuthUser $authUser, College $college): bool
    {
        return $authUser->can('ForceDelete:College');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:College');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:College');
    }

    public function replicate(AuthUser $authUser, College $college): bool
    {
        return $authUser->can('Replicate:College');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:College');
    }
}

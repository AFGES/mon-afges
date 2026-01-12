<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SessionSignIn;
use App\Models\User as AuthUser;

class SessionSignInPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SessionSignIn');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(AuthUser $authUser, SessionSignIn $sessionSignIn): bool
    {
        return $authUser->can('View:SessionSignIn');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SessionSignIn');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(AuthUser $authUser, SessionSignIn $sessionSignIn): bool
    {
        return $authUser->can('Update:SessionSignIn');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(AuthUser $authUser, SessionSignIn $sessionSignIn): bool
    {
        return $authUser->can('Delete:SessionSignIn');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(AuthUser $authUser, SessionSignIn $sessionSignIn): bool
    {
        return $authUser->can('Restore:SessionSignIn');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(AuthUser $authUser, SessionSignIn $sessionSignIn): bool
    {
        return $authUser->can('ForceDelete:SessionSignIn');
    }
}

<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SessionSignIn;
use App\Models\User as AuthUser;

final class SessionSignInPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SessionSignIn');
    }

    public function view(AuthUser $authUser, SessionSignIn $sessionSignIn): bool
    {
        return $authUser->can('View:SessionSignIn');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SessionSignIn');
    }

    public function update(AuthUser $authUser, SessionSignIn $sessionSignIn): bool
    {
        return $authUser->can('Update:SessionSignIn');
    }

    public function delete(AuthUser $authUser, SessionSignIn $sessionSignIn): bool
    {
        return $authUser->can('Delete:SessionSignIn');
    }

    public function restore(AuthUser $authUser, SessionSignIn $sessionSignIn): bool
    {
        return $authUser->can('Restore:SessionSignIn');
    }

    public function forceDelete(AuthUser $authUser, SessionSignIn $sessionSignIn): bool
    {
        return $authUser->can('ForceDelete:SessionSignIn');
    }
}

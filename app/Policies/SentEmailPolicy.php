<?php

declare(strict_types=1);

namespace App\Policies;

use App\Policies\Contracts\RestrictsSuperAdmin;
use FinityLabs\FinMail\Models\SentEmail;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * The sent-emails log is written by Fin Mail only: users read it and resend from it.
 */
final class SentEmailPolicy implements RestrictsSuperAdmin
{
    use HandlesAuthorization;

    /**
     * @return list<string>
     */
    public function superAdminRestrictedAbilities(): array
    {
        return [
            'create', 'update', 'delete', 'deleteAny', 'restore', 'restoreAny',
            'forceDelete', 'forceDeleteAny', 'replicate', 'reorder',
        ];
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SentEmail');
    }

    public function view(AuthUser $authUser, SentEmail $sentEmail): bool
    {
        return $authUser->can('View:SentEmail');
    }

    public function resend(AuthUser $authUser, SentEmail $sentEmail): bool
    {
        return $authUser->can('Resend:SentEmail');
    }

    public function create(AuthUser $authUser): bool
    {
        return false;
    }

    public function update(AuthUser $authUser, SentEmail $sentEmail): bool
    {
        return false;
    }

    public function delete(AuthUser $authUser, SentEmail $sentEmail): bool
    {
        return false;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restore(AuthUser $authUser, SentEmail $sentEmail): bool
    {
        return false;
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function forceDelete(AuthUser $authUser, SentEmail $sentEmail): bool
    {
        return false;
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function replicate(AuthUser $authUser, SentEmail $sentEmail): bool
    {
        return false;
    }

    public function reorder(AuthUser $authUser): bool
    {
        return false;
    }
}

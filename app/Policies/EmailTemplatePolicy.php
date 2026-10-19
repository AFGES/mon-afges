<?php

declare(strict_types=1);

namespace App\Policies;

use App\Policies\Contracts\RestrictsSuperAdmin;
use FinityLabs\FinMail\Models\EmailTemplate;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Templates are defined by code (see MailTemplateKey) and seeded by a migration: admins edit their
 * content, but nobody, the super admin included, creates or deletes them from the panel.
 */
final class EmailTemplatePolicy implements RestrictsSuperAdmin
{
    use HandlesAuthorization;

    /**
     * @return list<string>
     */
    public function superAdminRestrictedAbilities(): array
    {
        return [
            'create', 'delete', 'deleteAny', 'restore', 'restoreAny',
            'forceDelete', 'forceDeleteAny', 'replicate', 'reorder', 'compose',
        ];
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EmailTemplate');
    }

    public function view(AuthUser $authUser, EmailTemplate $emailTemplate): bool
    {
        return $authUser->can('View:EmailTemplate');
    }

    public function create(AuthUser $authUser): bool
    {
        return false;
    }

    public function update(AuthUser $authUser, EmailTemplate $emailTemplate): bool
    {
        return $authUser->can('Update:EmailTemplate');
    }

    public function delete(AuthUser $authUser, EmailTemplate $emailTemplate): bool
    {
        return false;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restore(AuthUser $authUser, EmailTemplate $emailTemplate): bool
    {
        return false;
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function forceDelete(AuthUser $authUser, EmailTemplate $emailTemplate): bool
    {
        return false;
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function replicate(AuthUser $authUser, EmailTemplate $emailTemplate): bool
    {
        return false;
    }

    public function reorder(AuthUser $authUser): bool
    {
        return false;
    }

    public function preview(AuthUser $authUser, EmailTemplate $emailTemplate): bool
    {
        return $authUser->can('Preview:EmailTemplate');
    }

    public function sendTest(AuthUser $authUser, EmailTemplate $emailTemplate): bool
    {
        return $authUser->can('SendTest:EmailTemplate');
    }

    public function compose(AuthUser $authUser, EmailTemplate $emailTemplate): bool
    {
        return false;
    }
}

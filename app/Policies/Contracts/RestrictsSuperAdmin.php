<?php

declare(strict_types=1);

namespace App\Policies\Contracts;

interface RestrictsSuperAdmin
{
    /**
     * Abilities the super admin is not granted automatically and that the policy must decide itself.
     *
     * @return list<string>
     */
    public function superAdminRestrictedAbilities(): array;
}

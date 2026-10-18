<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Spatie\Permission\Models\Role;

/**
 * @implements Scope<Role>
 */
final class ExcludeSuperAdminRoleScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereNot('name', User::SUPER_ADMIN_ROLE);
    }
}

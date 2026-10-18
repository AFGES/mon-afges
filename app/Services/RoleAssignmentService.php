<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

final class RoleAssignmentService
{
    public function isSuperAdmin(?Authenticatable $actor): bool
    {
        return $actor instanceof User && $actor->isSuperAdmin();
    }

    public function canAssignAnyRole(?Authenticatable $actor): bool
    {
        return $this->isSuperAdmin($actor);
    }

    /**
     * Ids of the roles the actor may add to or remove from a user: roles whose
     * permissions are all held by the actor, never the super admin role.
     *
     * @return Collection<int, int|string>
     */
    public function assignableRoleIds(?Authenticatable $actor): Collection
    {
        if (! $actor instanceof User) {
            return collect();
        }

        if ($actor->isSuperAdmin()) {
            return Role::query()->pluck('id');
        }

        $actorPermissionIds = $actor->getAllPermissions()->pluck('id')->all();

        return Role::query()
            ->where('name', '!=', User::SUPER_ADMIN_ROLE)
            ->whereDoesntHave('permissions', fn (Builder $query): Builder => $query->whereKeyNot($actorPermissionIds))
            ->pluck('id');
    }

    /**
     * Syncs the target's roles to the submitted ids. Non super admin actors can only
     * change roles they may assign; the target's other roles are kept untouched.
     *
     * @param  array<int|string, mixed>  $roleIds
     */
    public function syncRoles(?Authenticatable $actor, User $target, array $roleIds): void
    {
        $submittedIds = $this->normalizeIds($roleIds);

        if ($this->canAssignAnyRole($actor)) {
            $target->roles()->sync($submittedIds->all());

            return;
        }

        $assignableIds = $this->normalizeIds($this->assignableRoleIds($actor));
        $currentIds = $this->normalizeIds($target->roles()->pluck('id'));

        $target->roles()->sync(
            $submittedIds->intersect($assignableIds)
                ->merge($currentIds->diff($assignableIds))
                ->unique()
                ->values()
                ->all()
        );
    }

    /**
     * @param  iterable<int|string, mixed>  $ids
     * @return Collection<int, string>
     */
    public function normalizeIds(iterable $ids): Collection
    {
        return collect($ids)->map(fn (mixed $id): string => (string) $id)->values();
    }
}

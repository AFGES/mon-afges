<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

final class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('first_name')
                    ->label(__('user.fields.first_name'))
                    ->prefixIcon(Heroicon::OutlinedUser)
                    ->required()
                    ->maxLength(255),
                TextInput::make('last_name')
                    ->label(__('user.fields.last_name'))
                    ->prefixIcon(Heroicon::OutlinedUser)
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label(__('user.fields.email'))
                    ->prefixIcon(Heroicon::OutlinedEnvelope)
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at')
                    ->label(__('user.fields.email_verified_at'))
                    ->prefixIcon(Heroicon::OutlinedCheckBadge)
                    ->disabled(),
                TextInput::make('password')
                    ->label(__('user.fields.password'))
                    ->prefixIcon(Heroicon::OutlinedLockClosed)
                    ->password()
                    ->disabled(),
                Select::make('roles')
                    ->label(__('user.fields.roles'))
                    ->prefixIcon(Heroicon::OutlinedShieldCheck)
                    ->relationship(
                        name: 'roles',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query): Builder => self::canAssignAnyRole()
                            ? $query
                            : $query->whereKey(self::assignableRoleIds()),
                    )
                    ->afterStateHydrated(function (Select $component, mixed $state): void {
                        if (self::canAssignAnyRole()) {
                            return;
                        }

                        $assignableIds = self::normalizeIds(self::assignableRoleIds());

                        $component->state(
                            collect(Arr::wrap($state))
                                ->filter(fn (mixed $id): bool => $assignableIds->contains((string) $id))
                                ->values()
                                ->all()
                        );
                    })
                    ->saveRelationshipsUsing(function (User $record, mixed $state): void {
                        $submittedIds = self::normalizeIds(Arr::wrap($state));

                        if (self::canAssignAnyRole()) {
                            $record->roles()->sync($submittedIds->all());

                            return;
                        }

                        $assignableIds = self::normalizeIds(self::assignableRoleIds());
                        $currentIds = self::normalizeIds($record->roles()->pluck('id'));

                        $record->roles()->sync(
                            $submittedIds->intersect($assignableIds)
                                ->merge($currentIds->diff($assignableIds))
                                ->unique()
                                ->values()
                                ->all()
                        );
                    })
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ]);
    }

    private static function canAssignAnyRole(): bool
    {
        $authUser = auth()->user();

        return $authUser instanceof User && $authUser->isSuperAdmin();
    }

    /**
     * Ids of the roles the acting user may add to or remove from a user: roles whose
     * permissions are all held by the actor, never the super admin role.
     *
     * @return Collection<int, int|string>
     */
    private static function assignableRoleIds(): Collection
    {
        $authUser = auth()->user();

        if (! $authUser instanceof User) {
            return collect();
        }

        if ($authUser->isSuperAdmin()) {
            return Role::query()->pluck('id');
        }

        $actorPermissionIds = $authUser->getAllPermissions()->pluck('id')->all();

        return Role::query()
            ->where('name', '!=', User::SUPER_ADMIN_ROLE)
            ->whereDoesntHave('permissions', fn (Builder $query): Builder => $query->whereKeyNot($actorPermissionIds))
            ->pluck('id');
    }

    /**
     * @param  iterable<int|string, mixed>  $ids
     * @return Collection<int, string>
     */
    private static function normalizeIds(iterable $ids): Collection
    {
        return collect($ids)->map(fn (mixed $id): string => (string) $id)->values();
    }
}

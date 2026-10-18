<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use App\Services\RoleAssignmentService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

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
                        modifyQueryUsing: fn (Builder $query): Builder => self::roleAssignment()->canAssignAnyRole(auth()->user())
                            ? $query
                            : $query->whereKey(self::roleAssignment()->assignableRoleIds(auth()->user())),
                    )
                    ->afterStateHydrated(function (Select $component, mixed $state): void {
                        $service = self::roleAssignment();
                        $actor = auth()->user();

                        if ($service->canAssignAnyRole($actor)) {
                            return;
                        }

                        $assignableIds = $service->normalizeIds($service->assignableRoleIds($actor));

                        $component->state(
                            collect(Arr::wrap($state))
                                ->filter(fn (mixed $id): bool => $assignableIds->contains((string) $id))
                                ->values()
                                ->all()
                        );
                    })
                    ->saveRelationshipsUsing(function (User $record, mixed $state): void {
                        self::roleAssignment()->syncRoles(auth()->user(), $record, Arr::wrap($state));
                    })
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ]);
    }

    private static function roleAssignment(): RoleAssignmentService
    {
        return resolve(RoleAssignmentService::class);
    }
}

<?php

declare(strict_types=1);

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Livewire\livewire;

function actingAsSuperAdminForUserResource(): User
{
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole('super_admin');

    actingAs($user);

    return $user;
}

describe('ListUsers', function () {
    it('renders the page', function () {
        actingAsSuperAdminForUserResource();

        livewire(ListUsers::class)
            ->assertOk();
    });

    it('lists the existing users', function () {
        actingAsSuperAdminForUserResource();

        $users = User::factory()->count(5)->create();

        livewire(ListUsers::class)
            ->assertOk()
            ->assertCanSeeTableRecords($users);
    });

    it('lists only users matching the searched name', function () {
        actingAsSuperAdminForUserResource();

        $users = User::factory()
            ->count(5)
            ->sequence(
                ['first_name' => 'Aurelia', 'last_name' => 'Voss', 'email' => 'first@alpha.test'],
                ['first_name' => 'Bastien', 'last_name' => 'Corbel', 'email' => 'second@bravo.test'],
                ['first_name' => 'Cyrille', 'last_name' => 'Danton', 'email' => 'third@charlie.test'],
                ['first_name' => 'Delphine', 'last_name' => 'Ewing', 'email' => 'fourth@delta.test'],
                ['first_name' => 'Eloi', 'last_name' => 'Fabre', 'email' => 'fifth@echo.test'],
            )
            ->create();

        livewire(ListUsers::class)
            ->assertCanSeeTableRecords($users)
            ->searchTable($users->first()->name)
            ->assertCanSeeTableRecords($users->take(1))
            ->assertCanNotSeeTableRecords($users->skip(1));
    });

    it('lists only users matching the searched email', function () {
        actingAsSuperAdminForUserResource();

        $users = User::factory()
            ->count(5)
            ->sequence(
                ['first_name' => 'Aurelia', 'last_name' => 'Voss', 'email' => 'first@alpha.test'],
                ['first_name' => 'Bastien', 'last_name' => 'Corbel', 'email' => 'second@bravo.test'],
                ['first_name' => 'Cyrille', 'last_name' => 'Danton', 'email' => 'third@charlie.test'],
                ['first_name' => 'Delphine', 'last_name' => 'Ewing', 'email' => 'fourth@delta.test'],
                ['first_name' => 'Eloi', 'last_name' => 'Fabre', 'email' => 'fifth@echo.test'],
            )
            ->create();

        livewire(ListUsers::class)
            ->assertCanSeeTableRecords($users)
            ->searchTable($users->last()->email)
            ->assertCanSeeTableRecords($users->take(-1))
            ->assertCanNotSeeTableRecords($users->take($users->count() - 1));
    });
});

describe('CreateUser', function () {
    it('renders the page', function () {
        actingAsSuperAdminForUserResource();

        livewire(CreateUser::class)
            ->assertOk();
    });

    it('creates a user from valid form data', function () {
        actingAsSuperAdminForUserResource();

        $newUserData = User::factory()->make();

        livewire(CreateUser::class)
            ->fillForm([
                'first_name' => $newUserData->first_name,
                'last_name' => $newUserData->last_name,
                'email' => $newUserData->email,
                'password' => 'password123',
            ])
            ->call('create')
            ->assertNotified()
            ->assertRedirect();

        assertDatabaseHas(User::class, [
            'first_name' => $newUserData->first_name,
            'last_name' => $newUserData->last_name,
            'email' => $newUserData->email,
        ]);
    });

    it('rejects invalid form data on creation', function (array $data, array $errors) {
        actingAsSuperAdminForUserResource();

        livewire(CreateUser::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasFormErrors($errors)
            ->assertNotNotified()
            ->assertNoRedirect();
    })->with([
        '`name` is required' => [['first_name' => null, 'last_name' => 'User', 'email' => 'test@example.com'], ['first_name' => 'required']],
        '`last_name` is required' => [['first_name' => 'Test', 'last_name' => null, 'email' => 'test@example.com'], ['last_name' => 'required']],
        '`email` is a valid email address' => [['first_name' => 'Test', 'last_name' => 'User', 'email' => 'invalid-email'], ['email' => 'email']],
        '`email` is required' => [['first_name' => 'Test', 'last_name' => 'User', 'email' => null], ['email' => 'required']],
    ]);

    it('shows the translated required message for email', function () {
        actingAsSuperAdminForUserResource();

        livewire(CreateUser::class)
            ->fillForm(['first_name' => 'Test', 'last_name' => 'User', 'email' => null])
            ->call('create')
            ->assertHasFormErrors(['email' => [
                __('validation.required', ['attribute' => Str::lower(__('user.fields.email'))]),
            ]]);
    });

    it('shows the translated email message for email', function () {
        actingAsSuperAdminForUserResource();

        livewire(CreateUser::class)
            ->fillForm(['first_name' => 'Test', 'last_name' => 'User', 'email' => 'invalid-email'])
            ->call('create')
            ->assertHasFormErrors(['email' => [
                __('validation.email', ['attribute' => Str::lower(__('user.fields.email'))]),
            ]]);
    });
});

describe('EditUser', function () {
    it('renders the page', function () {
        actingAsSuperAdminForUserResource();

        $user = User::factory()->create();

        livewire(EditUser::class, [
            'record' => $user->id,
        ])
            ->assertOk()
            ->assertSchemaStateSet([
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
            ]);
    });

    it('updates a user from valid form data', function () {
        actingAsSuperAdminForUserResource();

        $user = User::factory()->create();
        $newUserData = User::factory()->make();

        livewire(EditUser::class, [
            'record' => $user->id,
        ])
            ->fillForm([
                'first_name' => $newUserData->first_name,
                'last_name' => $newUserData->last_name,
                'email' => $newUserData->email,
            ])
            ->call('save')
            ->assertNotified();

        assertDatabaseHas(User::class, [
            'id' => $user->id,
            'first_name' => $newUserData->first_name,
            'last_name' => $newUserData->last_name,
            'email' => $newUserData->email,
        ]);
    });

    it('rejects invalid form data on edit', function (array $data, array $errors) {
        actingAsSuperAdminForUserResource();

        $user = User::factory()->create();

        livewire(EditUser::class, [
            'record' => $user->id,
        ])
            ->fillForm($data)
            ->call('save')
            ->assertHasFormErrors($errors)
            ->assertNotNotified();
    })->with([
        '`name` is required' => [['first_name' => null, 'last_name' => 'User', 'email' => 'test@example.com'], ['first_name' => 'required']],
        '`last_name` is required' => [['first_name' => 'Test', 'last_name' => null, 'email' => 'test@example.com'], ['last_name' => 'required']],
        '`email` is a valid email address' => [['first_name' => 'Test', 'last_name' => 'User', 'email' => 'invalid-email'], ['email' => 'email']],
        '`email` is required' => [['first_name' => 'Test', 'last_name' => 'User', 'email' => null], ['email' => 'required']],
    ]);

    it('deletes a user when the delete action is called', function () {
        actingAsSuperAdminForUserResource();

        $user = User::factory()->create();

        livewire(EditUser::class, [
            'record' => $user->id,
        ])
            ->callAction(DeleteAction::class)
            ->assertNotified()
            ->assertRedirect();

        assertDatabaseMissing($user);
    });
});

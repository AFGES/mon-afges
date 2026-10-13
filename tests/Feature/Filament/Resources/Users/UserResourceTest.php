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
    it('can load the page', function () {
        actingAsSuperAdminForUserResource();

        livewire(ListUsers::class)
            ->assertOk();
    });

    it('can list users', function () {
        actingAsSuperAdminForUserResource();

        $users = User::factory()->count(5)->create();

        livewire(ListUsers::class)
            ->assertOk()
            ->assertCanSeeTableRecords($users);
    });

    it('can search users by name', function () {
        actingAsSuperAdminForUserResource();

        $users = User::factory()->count(5)->create();

        livewire(ListUsers::class)
            ->assertCanSeeTableRecords($users)
            ->searchTable($users->first()->name)
            ->assertCanSeeTableRecords($users->take(1))
            ->assertCanNotSeeTableRecords($users->skip(1));
    });

    it('can search users by email', function () {
        actingAsSuperAdminForUserResource();

        $users = User::factory()->count(5)->create();

        livewire(ListUsers::class)
            ->assertCanSeeTableRecords($users)
            ->searchTable($users->last()->email)
            ->assertCanSeeTableRecords($users->take(-1))
            ->assertCanNotSeeTableRecords($users->take($users->count() - 1));
    });
});

describe('CreateUser', function () {
    it('can load the page', function () {
        actingAsSuperAdminForUserResource();

        livewire(CreateUser::class)
            ->assertOk();
    });

    it('can create a user', function () {
        actingAsSuperAdminForUserResource();

        $newUserData = User::factory()->make();

        livewire(CreateUser::class)
            ->fillForm([
                'name' => $newUserData->name,
                'email' => $newUserData->email,
                'password' => 'password123',
            ])
            ->call('create')
            ->assertNotified()
            ->assertRedirect();

        assertDatabaseHas(User::class, [
            'name' => $newUserData->name,
            'email' => $newUserData->email,
        ]);
    });

    it('validates the form data', function (array $data, array $errors) {
        actingAsSuperAdminForUserResource();

        livewire(CreateUser::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasFormErrors($errors)
            ->assertNotNotified()
            ->assertNoRedirect();
    })->with([
        '`name` is required' => [['name' => null, 'email' => 'test@example.com'], ['name' => 'required']],
        '`email` is a valid email address' => [['name' => 'Test User', 'email' => 'invalid-email'], ['email' => 'email']],
        '`email` is required' => [['name' => 'Test User', 'email' => null], ['email' => 'required']],
    ]);

    it('shows the translated required message for email', function () {
        actingAsSuperAdminForUserResource();

        livewire(CreateUser::class)
            ->fillForm(['name' => 'Test User', 'email' => null])
            ->call('create')
            ->assertHasFormErrors(['email' => [
                __('validation.required', ['attribute' => Str::lower(__('user.fields.email'))]),
            ]]);
    });

    it('shows the translated email message for email', function () {
        actingAsSuperAdminForUserResource();

        livewire(CreateUser::class)
            ->fillForm(['name' => 'Test User', 'email' => 'invalid-email'])
            ->call('create')
            ->assertHasFormErrors(['email' => [
                __('validation.email', ['attribute' => Str::lower(__('user.fields.email'))]),
            ]]);
    });
});

describe('EditUser', function () {
    it('can load the page', function () {
        actingAsSuperAdminForUserResource();

        $user = User::factory()->create();

        livewire(EditUser::class, [
            'record' => $user->id,
        ])
            ->assertOk()
            ->assertSchemaStateSet([
                'name' => $user->name,
                'email' => $user->email,
            ]);
    });

    it('can update a user', function () {
        actingAsSuperAdminForUserResource();

        $user = User::factory()->create();
        $newUserData = User::factory()->make();

        livewire(EditUser::class, [
            'record' => $user->id,
        ])
            ->fillForm([
                'name' => $newUserData->name,
                'email' => $newUserData->email,
            ])
            ->call('save')
            ->assertNotified();

        assertDatabaseHas(User::class, [
            'id' => $user->id,
            'name' => $newUserData->name,
            'email' => $newUserData->email,
        ]);
    });

    it('validates the form data', function (array $data, array $errors) {
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
        '`name` is required' => [['name' => null, 'email' => 'test@example.com'], ['name' => 'required']],
        '`email` is a valid email address' => [['name' => 'Test User', 'email' => 'invalid-email'], ['email' => 'email']],
        '`email` is required' => [['name' => 'Test User', 'email' => null], ['email' => 'required']],
    ]);

    it('can delete a user', function () {
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

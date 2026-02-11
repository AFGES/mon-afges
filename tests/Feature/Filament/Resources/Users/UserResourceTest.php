<?php

declare(strict_types=1);

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Livewire\livewire;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $this->actingAsUser = User::factory()->create();
    $this->actingAsUser->assignRole('super_admin');
    actingAs($this->actingAsUser);
});

describe('ListUsers', function () {
    it('can load the page', function () {
        livewire(ListUsers::class)
            ->assertOk();
    });

    it('can list users', function () {
        $users = User::factory()->count(5)->create();

        livewire(ListUsers::class)
            ->assertOk()
            ->assertCanSeeTableRecords($users);
    });

    it('can search users by name', function () {
        $users = User::factory()->count(5)->create();

        livewire(ListUsers::class)
            ->assertCanSeeTableRecords($users)
            ->searchTable($users->first()->name)
            ->assertCanSeeTableRecords($users->take(1))
            ->assertCanNotSeeTableRecords($users->skip(1));
    });

    it('can search users by email', function () {
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
        livewire(CreateUser::class)
            ->assertOk();
    });

    it('can create a user', function () {
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
});

describe('EditUser', function () {
    it('can load the page', function () {
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

<?php

declare(strict_types=1);

use App\Filament\Resources\Users\RelationManagers\DelegatesRelationManager;
use App\Filament\Resources\Users\RelationManagers\VolunteersRelationManager;
use App\Models\Delegate;
use App\Models\Organization;
use App\Models\Seat;
use App\Models\User;
use App\Models\Volunteer;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

function actingAsSuperAdminForUserRelationManagers(): User
{
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole('super_admin');

    actingAs($user);

    return $user;
}

describe('DelegatesRelationManager', function () {
    it('can render the relation manager', function () {
        actingAsSuperAdminForUserRelationManagers();

        $user = User::factory()->create();

        Livewire::test(DelegatesRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => App\Filament\Resources\Users\Pages\EditUser::class,
        ])
            ->assertOk();
    });

    it('can list delegates for a user', function () {
        actingAsSuperAdminForUserRelationManagers();

        $user = User::factory()->create();
        $delegates = Delegate::factory()->count(3)->for($user)->approved()->create();
        $otherUserDelegate = Delegate::factory()->approved()->create();

        Livewire::test(DelegatesRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => App\Filament\Resources\Users\Pages\EditUser::class,
        ])
            ->assertCanSeeTableRecords($delegates)
            ->assertCanNotSeeTableRecords([$otherUserDelegate]);
    });

    it('can create a delegate for a user', function () {
        actingAsSuperAdminForUserRelationManagers();

        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $seat = Seat::factory()->create();

        Livewire::test(DelegatesRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => App\Filament\Resources\Users\Pages\EditUser::class,
        ])
            ->callTableAction('create', data: [
                'organization_id' => $organization->id,
                'seat_id' => $seat->id,
                'email' => fake()->email(),
                'started_at' => now()->toDateString(),
            ])
            ->assertNotified();

        assertDatabaseHas(Delegate::class, [
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ]);
    });

    it('can edit a delegate', function () {
        actingAsSuperAdminForUserRelationManagers();

        $user = User::factory()->create();
        $delegate = Delegate::factory()->for($user)->approved()->create();
        $newEmail = fake()->email();

        Livewire::test(DelegatesRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => App\Filament\Resources\Users\Pages\EditUser::class,
        ])
            ->callTableAction('edit', $delegate, data: [
                'email' => $newEmail,
            ])
            ->assertNotified();

        assertDatabaseHas(Delegate::class, [
            'id' => $delegate->id,
            'email' => $newEmail,
        ]);
    });
});

describe('VolunteersRelationManager', function () {
    it('can render the relation manager', function () {
        actingAsSuperAdminForUserRelationManagers();

        $user = User::factory()->create();

        Livewire::test(VolunteersRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => App\Filament\Resources\Users\Pages\EditUser::class,
        ])
            ->assertOk();
    });

    it('can list volunteers for a user', function () {
        actingAsSuperAdminForUserRelationManagers();

        $user = User::factory()->create();
        $volunteers = Volunteer::factory()->count(3)->for($user)->approved()->create();
        $otherUserVolunteer = Volunteer::factory()->approved()->create();

        Livewire::test(VolunteersRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => App\Filament\Resources\Users\Pages\EditUser::class,
        ])
            ->assertCanSeeTableRecords($volunteers)
            ->assertCanNotSeeTableRecords([$otherUserVolunteer]);
    });

    it('can create a volunteer for a user', function () {
        actingAsSuperAdminForUserRelationManagers();

        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        Livewire::test(VolunteersRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => App\Filament\Resources\Users\Pages\EditUser::class,
        ])
            ->callTableAction('create', data: [
                'organization_id' => $organization->id,
                'position_title' => fake()->jobTitle(),
                'started_at' => now()->toDateString(),
            ])
            ->assertNotified();

        assertDatabaseHas(Volunteer::class, [
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ]);
    });

    it('can edit a volunteer', function () {
        actingAsSuperAdminForUserRelationManagers();

        $user = User::factory()->create();
        $volunteer = Volunteer::factory()->for($user)->approved()->create();
        $newPositionTitle = fake()->jobTitle();

        Livewire::test(VolunteersRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => App\Filament\Resources\Users\Pages\EditUser::class,
        ])
            ->callTableAction('edit', $volunteer, data: [
                'position_title' => $newPositionTitle,
            ])
            ->assertNotified();

        assertDatabaseHas(Volunteer::class, [
            'id' => $volunteer->id,
            'position_title' => $newPositionTitle,
        ]);
    });
});

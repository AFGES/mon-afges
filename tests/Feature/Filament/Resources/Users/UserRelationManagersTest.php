<?php

declare(strict_types=1);

use App\Enums\ApprovalStatus;
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
    it('renders the relation manager', function () {
        actingAsSuperAdminForUserRelationManagers();

        $user = User::factory()->create();

        Livewire::test(DelegatesRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => App\Filament\Resources\Users\Pages\EditUser::class,
        ])
            ->assertOk();
    });

    it('lists only the delegates belonging to the user', function () {
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

    it('creates an already approved delegate for a user from valid form data', function () {
        $admin = actingAsSuperAdminForUserRelationManagers();

        $user = User::factory()->create();
        $seat = Seat::factory()->create();
        $organization = $seat->collegeOrganization->organization;

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
            'approval_status' => ApprovalStatus::Approved->value,
            'approved_by' => $admin->id,
        ]);
    });

    it('updates the delegate through the edit table action', function () {
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
    it('renders the relation manager', function () {
        actingAsSuperAdminForUserRelationManagers();

        $user = User::factory()->create();

        Livewire::test(VolunteersRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => App\Filament\Resources\Users\Pages\EditUser::class,
        ])
            ->assertOk();
    });

    it('lists only the volunteers belonging to the user', function () {
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

    it('creates a volunteer for a user from valid form data', function () {
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

    it('updates the volunteer through the edit table action', function () {
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

<?php

declare(strict_types=1);

use App\Filament\Resources\Organizations\RelationManagers\DelegatesRelationManager;
use App\Filament\Resources\Organizations\RelationManagers\VolunteersRelationManager;
use App\Models\Delegate;
use App\Models\Organization;
use App\Models\Seat;
use App\Models\User;
use App\Models\Volunteer;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

function actingAsSuperAdminForOrganizationRelationManagers(): User
{
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole('super_admin');

    actingAs($user);

    return $user;
}

describe('DelegatesRelationManager', function () {
    it('can render the relation manager', function () {
        actingAsSuperAdminForOrganizationRelationManagers();

        $organization = Organization::factory()->create();

        Livewire::test(DelegatesRelationManager::class, [
            'ownerRecord' => $organization,
            'pageClass' => App\Filament\Resources\Organizations\Pages\EditOrganization::class,
        ])
            ->assertOk();
    });

    it('can list delegates for an organization', function () {
        actingAsSuperAdminForOrganizationRelationManagers();

        $organization = Organization::factory()->create();
        $delegates = Delegate::factory()->count(3)->for($organization)->approved()->create();
        $otherOrgDelegate = Delegate::factory()->approved()->create();

        Livewire::test(DelegatesRelationManager::class, [
            'ownerRecord' => $organization,
            'pageClass' => App\Filament\Resources\Organizations\Pages\EditOrganization::class,
        ])
            ->assertCanSeeTableRecords($delegates)
            ->assertCanNotSeeTableRecords([$otherOrgDelegate]);
    });

    it('can create a delegate for an organization', function () {
        actingAsSuperAdminForOrganizationRelationManagers();

        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $seat = Seat::factory()->create();

        Livewire::test(DelegatesRelationManager::class, [
            'ownerRecord' => $organization,
            'pageClass' => App\Filament\Resources\Organizations\Pages\EditOrganization::class,
        ])
            ->callTableAction('create', data: [
                'user_id' => $user->id,
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
        actingAsSuperAdminForOrganizationRelationManagers();

        $organization = Organization::factory()->create();
        $delegate = Delegate::factory()->for($organization)->approved()->create();
        $newEmail = fake()->email();

        Livewire::test(DelegatesRelationManager::class, [
            'ownerRecord' => $organization,
            'pageClass' => App\Filament\Resources\Organizations\Pages\EditOrganization::class,
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
        actingAsSuperAdminForOrganizationRelationManagers();

        $organization = Organization::factory()->create();

        Livewire::test(VolunteersRelationManager::class, [
            'ownerRecord' => $organization,
            'pageClass' => App\Filament\Resources\Organizations\Pages\EditOrganization::class,
        ])
            ->assertOk();
    });

    it('can list volunteers for an organization', function () {
        actingAsSuperAdminForOrganizationRelationManagers();

        $organization = Organization::factory()->create();
        $volunteers = Volunteer::factory()->count(3)->for($organization)->approved()->create();
        $otherOrgVolunteer = Volunteer::factory()->approved()->create();

        Livewire::test(VolunteersRelationManager::class, [
            'ownerRecord' => $organization,
            'pageClass' => App\Filament\Resources\Organizations\Pages\EditOrganization::class,
        ])
            ->assertCanSeeTableRecords($volunteers)
            ->assertCanNotSeeTableRecords([$otherOrgVolunteer]);
    });

    it('can create a volunteer for an organization', function () {
        actingAsSuperAdminForOrganizationRelationManagers();

        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        Livewire::test(VolunteersRelationManager::class, [
            'ownerRecord' => $organization,
            'pageClass' => App\Filament\Resources\Organizations\Pages\EditOrganization::class,
        ])
            ->callTableAction('create', data: [
                'user_id' => $user->id,
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
        actingAsSuperAdminForOrganizationRelationManagers();

        $organization = Organization::factory()->create();
        $volunteer = Volunteer::factory()->for($organization)->approved()->create();
        $newPositionTitle = fake()->jobTitle();

        Livewire::test(VolunteersRelationManager::class, [
            'ownerRecord' => $organization,
            'pageClass' => App\Filament\Resources\Organizations\Pages\EditOrganization::class,
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

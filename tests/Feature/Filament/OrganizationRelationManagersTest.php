<?php

declare(strict_types=1);

use App\Filament\Resources\Organizations\Pages\EditOrganization;
use App\Filament\Resources\Organizations\RelationManagers\DelegatesRelationManager;
use App\Filament\Resources\Organizations\RelationManagers\VolunteersRelationManager;
use App\Models\Delegate;
use App\Models\Organization;
use App\Models\User;
use App\Models\Volunteer;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'admin@afges.org',
    ]);
    $this->actingAs($this->user);
});

test('can load delegates relation manager for organization', function () {
    $organization = Organization::factory()->create();
    $delegates = Delegate::factory()->count(3)->create([
        'organization_id' => $organization->id,
    ]);

    Livewire::test(DelegatesRelationManager::class, [
        'ownerRecord' => $organization,
        'pageClass' => EditOrganization::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords($delegates);
});

test('can load volunteers relation manager for organization', function () {
    $organization = Organization::factory()->create();
    $volunteers = Volunteer::factory()->count(3)->create([
        'organization_id' => $organization->id,
    ]);

    Livewire::test(VolunteersRelationManager::class, [
        'ownerRecord' => $organization,
        'pageClass' => EditOrganization::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords($volunteers);
});

test('organization delegates relation manager shows pending delegates by default', function () {
    $organization = Organization::factory()->create();
    
    $pendingDelegate = Delegate::factory()->create([
        'organization_id' => $organization->id,
    ]);
    
    $approvedDelegate = Delegate::factory()->approved()->create([
        'organization_id' => $organization->id,
    ]);

    Livewire::test(DelegatesRelationManager::class, [
        'ownerRecord' => $organization,
        'pageClass' => EditOrganization::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$pendingDelegate])
        ->assertCanNotSeeTableRecords([$approvedDelegate]);
});

test('organization volunteers relation manager shows pending volunteers by default', function () {
    $organization = Organization::factory()->create();
    
    $pendingVolunteer = Volunteer::factory()->create([
        'organization_id' => $organization->id,
    ]);
    
    $approvedVolunteer = Volunteer::factory()->approved()->create([
        'organization_id' => $organization->id,
    ]);

    Livewire::test(VolunteersRelationManager::class, [
        'ownerRecord' => $organization,
        'pageClass' => EditOrganization::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$pendingVolunteer])
        ->assertCanNotSeeTableRecords([$approvedVolunteer]);
});

<?php

declare(strict_types=1);

use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\RelationManagers\DelegatesRelationManager;
use App\Filament\Resources\Users\RelationManagers\VolunteersRelationManager;
use App\Models\Delegate;
use App\Models\User;
use App\Models\Volunteer;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'admin@afges.org',
    ]);
    $this->actingAs($this->user);
});

test('can load delegates relation manager', function () {
    $user = User::factory()->create();
    $delegates = Delegate::factory()->count(3)->create([
        'user_id' => $user->id,
    ]);

    Livewire::test(DelegatesRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => EditUser::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords($delegates);
});

test('can load volunteers relation manager', function () {
    $user = User::factory()->create();
    $volunteers = Volunteer::factory()->count(3)->create([
        'user_id' => $user->id,
    ]);

    Livewire::test(VolunteersRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => EditUser::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords($volunteers);
});

test('delegates relation manager shows pending delegates by default', function () {
    $user = User::factory()->create();
    
    $pendingDelegate = Delegate::factory()->create([
        'user_id' => $user->id,
    ]);
    
    $approvedDelegate = Delegate::factory()->approved()->create([
        'user_id' => $user->id,
    ]);

    Livewire::test(DelegatesRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => EditUser::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$pendingDelegate])
        ->assertCanNotSeeTableRecords([$approvedDelegate]);
});

test('volunteers relation manager shows pending volunteers by default', function () {
    $user = User::factory()->create();
    
    $pendingVolunteer = Volunteer::factory()->create([
        'user_id' => $user->id,
    ]);
    
    $approvedVolunteer = Volunteer::factory()->approved()->create([
        'user_id' => $user->id,
    ]);

    Livewire::test(VolunteersRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => EditUser::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$pendingVolunteer])
        ->assertCanNotSeeTableRecords([$approvedVolunteer]);
});

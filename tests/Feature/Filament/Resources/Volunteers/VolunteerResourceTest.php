<?php

declare(strict_types=1);

use App\Enums\ApprovalStatus;
use App\Filament\Resources\Volunteers\Pages\CreateVolunteer;
use App\Filament\Resources\Volunteers\Pages\EditVolunteer;
use App\Filament\Resources\Volunteers\Pages\ListVolunteers;
use App\Models\Organization;
use App\Models\User;
use App\Models\Volunteer;
use Filament\Actions\DeleteAction;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $this->actingAsUser = User::factory()->create();
    $this->actingAsUser->assignRole('super_admin');
    actingAs($this->actingAsUser);
});

describe('ListVolunteers', function () {
    it('can load the page', function () {
        livewire(ListVolunteers::class)
            ->assertOk();
    });

    it('can list volunteers', function () {
        $volunteers = Volunteer::factory()->count(5)->approved()->create();

        livewire(ListVolunteers::class)
            ->assertOk()
            ->assertCanSeeTableRecords($volunteers);
    });

    it('can search volunteers', function () {
        $volunteers = Volunteer::factory()->count(5)->approved()->create();

        livewire(ListVolunteers::class)
            ->assertCanSeeTableRecords($volunteers)
            ->searchTable($volunteers->first()->position_title)
            ->assertCanSeeTableRecords($volunteers->take(1))
            ->assertCanNotSeeTableRecords($volunteers->skip(1));
    });
});

describe('CreateVolunteer', function () {
    it('can load the page', function () {
        livewire(CreateVolunteer::class)
            ->assertOk();
    });

    it('can create a volunteer', function () {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $positionTitle = fake()->jobTitle();

        livewire(CreateVolunteer::class)
            ->fillForm([
                'user_id' => $user->id,
                'organization_id' => $organization->id,
                'position_title' => $positionTitle,
                'started_at' => now()->toDateString(),
                'approval_status' => ApprovalStatus::Pending->value,
            ])
            ->call('create')
            ->assertNotified()
            ->assertRedirect();

        assertDatabaseHas(Volunteer::class, [
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'position_title' => $positionTitle,
            'approval_status' => ApprovalStatus::Pending,
        ]);
    });

    it('validates the form data', function (array $data, array $errors) {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        livewire(CreateVolunteer::class)
            ->fillForm([
                'user_id' => $user->id,
                'organization_id' => $organization->id,
                'started_at' => now()->toDateString(),
                ...$data,
            ])
            ->call('create')
            ->assertHasFormErrors($errors)
            ->assertNotNotified()
            ->assertNoRedirect();
    })->with([
        '`user_id` is required' => [['user_id' => null], ['user_id' => 'required']],
        '`organization_id` is required' => [['organization_id' => null], ['organization_id' => 'required']],
        '`started_at` is required' => [['started_at' => null], ['started_at' => 'required']],
    ]);
});

describe('EditVolunteer', function () {
    it('can load the page', function () {
        $volunteer = Volunteer::factory()->approved()->create();

        livewire(EditVolunteer::class, [
            'record' => $volunteer->id,
        ])
            ->assertOk()
            ->assertSchemaStateSet([
                'position_title' => $volunteer->position_title,
            ]);
    });

    it('can update a volunteer', function () {
        $volunteer = Volunteer::factory()->approved()->create();
        $newPositionTitle = fake()->jobTitle();

        livewire(EditVolunteer::class, [
            'record' => $volunteer->id,
        ])
            ->fillForm([
                'position_title' => $newPositionTitle,
            ])
            ->call('save')
            ->assertNotified();

        assertDatabaseHas(Volunteer::class, [
            'id' => $volunteer->id,
            'position_title' => $newPositionTitle,
        ]);
    });

    it('can soft delete a volunteer', function () {
        $volunteer = Volunteer::factory()->approved()->create();

        livewire(EditVolunteer::class, [
            'record' => $volunteer->id,
        ])
            ->callAction(DeleteAction::class)
            ->assertNotified()
            ->assertRedirect();

        expect($volunteer->fresh()->trashed())->toBeTrue();
    });
});

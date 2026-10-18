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
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

function actingAsSuperAdminForVolunteerResource(): User
{
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole('super_admin');

    actingAs($user);

    return $user;
}

describe('ListVolunteers', function () {
    it('renders the page', function () {
        actingAsSuperAdminForVolunteerResource();

        livewire(ListVolunteers::class)
            ->assertOk();
    });

    it('lists the existing volunteers', function () {
        actingAsSuperAdminForVolunteerResource();

        $volunteers = Volunteer::factory()->count(5)->approved()->create();

        livewire(ListVolunteers::class)
            ->assertOk()
            ->assertCanSeeTableRecords($volunteers);
    });

    it('lists pending volunteers although they are not approved', function () {
        actingAsSuperAdminForVolunteerResource();

        $pendingVolunteer = Volunteer::factory()->pending()->create();
        $approvedVolunteer = Volunteer::factory()->approved()->create();

        livewire(ListVolunteers::class)
            ->assertCanSeeTableRecords([$pendingVolunteer])
            ->assertCanNotSeeTableRecords([$approvedVolunteer])
            ->filterTable('approval_status', ['approved'])
            ->assertCanSeeTableRecords([$approvedVolunteer])
            ->assertCanNotSeeTableRecords([$pendingVolunteer]);
    });

    it('hides soft-deleted volunteers unless the trashed filter is set', function () {
        actingAsSuperAdminForVolunteerResource();

        $trashed = Volunteer::factory()->pending()->create();
        $trashed->delete();

        livewire(ListVolunteers::class)
            ->assertCanNotSeeTableRecords([$trashed])
            ->removeTableFilters()
            ->filterTable('trashed', '0')
            ->assertCanSeeTableRecords([$trashed]);
    });

    it('lists only volunteers matching the searched term', function () {
        actingAsSuperAdminForVolunteerResource();

        $volunteers = Volunteer::factory()
            ->count(5)
            ->approved()
            ->sequence(
                ['position_title' => 'Treasurer'],
                ['position_title' => 'Secretary'],
                ['position_title' => 'Chairperson'],
                ['position_title' => 'Auditor'],
                ['position_title' => 'Moderator'],
            )
            ->create();

        livewire(ListVolunteers::class)
            ->assertCanSeeTableRecords($volunteers)
            ->searchTable($volunteers->first()->position_title)
            ->assertCanSeeTableRecords($volunteers->take(1))
            ->assertCanNotSeeTableRecords($volunteers->skip(1));
    });
});

describe('CreateVolunteer', function () {
    it('renders the page', function () {
        actingAsSuperAdminForVolunteerResource();

        livewire(CreateVolunteer::class)
            ->assertOk();
    });

    it('creates a volunteer from valid form data', function () {
        actingAsSuperAdminForVolunteerResource();

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

    it('rejects invalid form data on creation', function (array $data, array $errors) {
        actingAsSuperAdminForVolunteerResource();

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

    it('shows the translated required message for user_id', function () {
        actingAsSuperAdminForVolunteerResource();

        livewire(CreateVolunteer::class)
            ->fillForm(['organization_id' => Organization::factory()->create()->id, 'started_at' => '2026-01-01', 'user_id' => null])
            ->call('create')
            ->assertHasFormErrors(['user_id' => [
                __('validation.required', ['attribute' => Str::lower(__('volunteer.fields.user'))]),
            ]]);
    });
});

describe('EditVolunteer', function () {
    it('renders the page', function () {
        actingAsSuperAdminForVolunteerResource();

        $volunteer = Volunteer::factory()->approved()->create();

        livewire(EditVolunteer::class, [
            'record' => $volunteer->id,
        ])
            ->assertOk()
            ->assertSchemaStateSet([
                'position_title' => $volunteer->position_title,
            ]);
    });

    it('updates a volunteer from valid form data', function () {
        actingAsSuperAdminForVolunteerResource();

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

    it('soft deletes the volunteer when the delete action is called', function () {
        actingAsSuperAdminForVolunteerResource();

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

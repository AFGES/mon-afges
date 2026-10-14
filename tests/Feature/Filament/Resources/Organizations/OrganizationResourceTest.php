<?php

declare(strict_types=1);

use App\Filament\Resources\Organizations\Pages\CreateOrganization;
use App\Filament\Resources\Organizations\Pages\EditOrganization;
use App\Filament\Resources\Organizations\Pages\ListOrganizations;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

function actingAsSuperAdminForOrganizationResource(): User
{
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole('super_admin');

    actingAs($user);

    return $user;
}

describe('ListOrganizations', function () {
    it('renders the page', function () {
        actingAsSuperAdminForOrganizationResource();

        livewire(ListOrganizations::class)
            ->assertOk();
    });

    it('lists the existing organizations', function () {
        actingAsSuperAdminForOrganizationResource();

        $organizations = Organization::factory()->count(5)->create();

        livewire(ListOrganizations::class)
            ->assertOk()
            ->assertCanSeeTableRecords($organizations);
    });

    it('lists only organizations matching the searched short name', function () {
        actingAsSuperAdminForOrganizationResource();

        $organizations = Organization::factory()
            ->count(5)
            ->sequence(
                ['short_name' => 'Alpha', 'legal_name' => 'Association Alpha'],
                ['short_name' => 'Bravo', 'legal_name' => 'Association Bravo'],
                ['short_name' => 'Charlie', 'legal_name' => 'Association Charlie'],
                ['short_name' => 'Delta', 'legal_name' => 'Association Delta'],
                ['short_name' => 'Echo', 'legal_name' => 'Association Echo'],
            )
            ->create();

        livewire(ListOrganizations::class)
            ->assertCanSeeTableRecords($organizations)
            ->searchTable($organizations->first()->short_name)
            ->assertCanSeeTableRecords($organizations->take(1))
            ->assertCanNotSeeTableRecords($organizations->skip(1));
    });

    it('lists only organizations matching the searched legal name', function () {
        actingAsSuperAdminForOrganizationResource();

        $organizations = Organization::factory()
            ->count(5)
            ->sequence(
                ['short_name' => 'A1', 'legal_name' => 'Fédération Alpha'],
                ['short_name' => 'B2', 'legal_name' => 'Fédération Bravo'],
                ['short_name' => 'C3', 'legal_name' => 'Fédération Charlie'],
                ['short_name' => 'D4', 'legal_name' => 'Fédération Delta'],
                ['short_name' => 'E5', 'legal_name' => 'Fédération Echo'],
            )
            ->create();

        livewire(ListOrganizations::class)
            ->assertCanSeeTableRecords($organizations)
            ->searchTable($organizations->last()->legal_name)
            ->assertCanSeeTableRecords($organizations->take(-1))
            ->assertCanNotSeeTableRecords($organizations->take($organizations->count() - 1));
    });
});

describe('CreateOrganization', function () {
    it('renders the page', function () {
        actingAsSuperAdminForOrganizationResource();

        livewire(CreateOrganization::class)
            ->assertOk();
    });

    it('creates an organization from valid form data', function () {
        actingAsSuperAdminForOrganizationResource();

        $newOrgData = Organization::factory()->make();

        livewire(CreateOrganization::class)
            ->fillForm([
                'short_name' => $newOrgData->short_name,
                'legal_name' => $newOrgData->legal_name,
                'domain' => $newOrgData->domain,
            ])
            ->call('create')
            ->assertNotified()
            ->assertRedirect();

        assertDatabaseHas(Organization::class, [
            'short_name' => $newOrgData->short_name,
            'legal_name' => $newOrgData->legal_name,
        ]);
    });

    it('rejects invalid form data on creation', function (array $data, array $errors) {
        actingAsSuperAdminForOrganizationResource();

        $newOrgData = Organization::factory()->make();

        livewire(CreateOrganization::class)
            ->fillForm([
                'short_name' => $newOrgData->short_name,
                'legal_name' => $newOrgData->legal_name,
                ...$data,
            ])
            ->call('create')
            ->assertHasFormErrors($errors)
            ->assertNotNotified()
            ->assertNoRedirect();
    })->with([
        '`short_name` is required' => [['short_name' => null], ['short_name' => 'required']],
        '`short_name` is max 255 characters' => [['short_name' => Str::random(256)], ['short_name' => 'max']],
        '`legal_name` is required' => [['legal_name' => null], ['legal_name' => 'required']],
        '`legal_name` is max 255 characters' => [['legal_name' => Str::random(256)], ['legal_name' => 'max']],
    ]);

    it('shows the translated required message for short_name', function () {
        actingAsSuperAdminForOrganizationResource();

        livewire(CreateOrganization::class)
            ->fillForm(['legal_name' => fake()->unique()->company(), 'short_name' => null])
            ->call('create')
            ->assertHasFormErrors(['short_name' => [
                __('validation.required', ['attribute' => Str::lower(__('organization.fields.short_name'))]),
            ]]);
    });

    it('shows the translated max message for short_name', function () {
        actingAsSuperAdminForOrganizationResource();

        livewire(CreateOrganization::class)
            ->fillForm(['legal_name' => fake()->unique()->company(), 'short_name' => Str::random(256)])
            ->call('create')
            ->assertHasFormErrors(['short_name' => [
                __('validation.max.string', ['attribute' => Str::lower(__('organization.fields.short_name')), 'max' => 255]),
            ]]);
    });
});

describe('EditOrganization', function () {
    it('renders the page', function () {
        actingAsSuperAdminForOrganizationResource();

        $organization = Organization::factory()->create();

        livewire(EditOrganization::class, [
            'record' => $organization->id,
        ])
            ->assertOk()
            ->assertSchemaStateSet([
                'short_name' => $organization->short_name,
                'legal_name' => $organization->legal_name,
            ]);
    });

    it('updates an organization from valid form data', function () {
        actingAsSuperAdminForOrganizationResource();

        $organization = Organization::factory()->create();
        $newOrgData = Organization::factory()->make();

        livewire(EditOrganization::class, [
            'record' => $organization->id,
        ])
            ->fillForm([
                'short_name' => $newOrgData->short_name,
                'legal_name' => $newOrgData->legal_name,
            ])
            ->call('save')
            ->assertNotified();

        assertDatabaseHas(Organization::class, [
            'id' => $organization->id,
            'short_name' => $newOrgData->short_name,
            'legal_name' => $newOrgData->legal_name,
        ]);
    });

    it('rejects invalid form data on edit', function (array $data, array $errors) {
        actingAsSuperAdminForOrganizationResource();

        $organization = Organization::factory()->create();
        $newOrgData = Organization::factory()->make();

        livewire(EditOrganization::class, [
            'record' => $organization->id,
        ])
            ->fillForm([
                'short_name' => $newOrgData->short_name,
                'legal_name' => $newOrgData->legal_name,
                ...$data,
            ])
            ->call('save')
            ->assertHasFormErrors($errors)
            ->assertNotNotified();
    })->with([
        '`short_name` is required' => [['short_name' => null], ['short_name' => 'required']],
        '`short_name` is max 255 characters' => [['short_name' => Str::random(256)], ['short_name' => 'max']],
        '`legal_name` is required' => [['legal_name' => null], ['legal_name' => 'required']],
        '`legal_name` is max 255 characters' => [['legal_name' => Str::random(256)], ['legal_name' => 'max']],
    ]);

    it('soft deletes the organization when the delete action is called', function () {
        actingAsSuperAdminForOrganizationResource();

        $organization = Organization::factory()->create();

        livewire(EditOrganization::class, [
            'record' => $organization->id,
        ])
            ->callAction(DeleteAction::class)
            ->assertNotified()
            ->assertRedirect();

        expect($organization->fresh()->trashed())->toBeTrue();
    });
});

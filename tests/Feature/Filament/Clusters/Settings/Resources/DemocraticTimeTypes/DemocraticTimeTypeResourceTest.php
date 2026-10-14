<?php

declare(strict_types=1);

use App\Filament\Clusters\Settings\Resources\DemocraticTimeTypes\Pages\CreateDemocraticTimeType;
use App\Filament\Clusters\Settings\Resources\DemocraticTimeTypes\Pages\EditDemocraticTimeType;
use App\Filament\Clusters\Settings\Resources\DemocraticTimeTypes\Pages\ListDemocraticTimeTypes;
use App\Models\DemocraticTimeType;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

function actingAsSuperAdminForDemocraticTimeTypeResource(): User
{
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole('super_admin');

    actingAs($user);

    return $user;
}

describe('ListDemocraticTimeTypes', function () {
    it('renders the page', function () {
        actingAsSuperAdminForDemocraticTimeTypeResource();

        Livewire::test(ListDemocraticTimeTypes::class)
            ->assertOk();
    });

    it('lists the existing democratic time types', function () {
        actingAsSuperAdminForDemocraticTimeTypeResource();

        $types = DemocraticTimeType::factory()->count(3)->create();

        Livewire::test(ListDemocraticTimeTypes::class)
            ->assertCanSeeTableRecords($types);
    });

    it('lists only democratic time types matching the searched name', function () {
        actingAsSuperAdminForDemocraticTimeTypeResource();

        $types = DemocraticTimeType::factory()
            ->count(3)
            ->sequence(
                ['name' => 'Alpha Assembly', 'acronym' => 'AAA'],
                ['name' => 'Bravo Board', 'acronym' => 'BBB'],
                ['name' => 'Charlie Committee', 'acronym' => 'CCC'],
            )
            ->create();

        Livewire::test(ListDemocraticTimeTypes::class)
            ->searchTable($types->first()->name)
            ->assertCanSeeTableRecords($types->take(1))
            ->assertCanNotSeeTableRecords($types->skip(1));
    });

    it('orders democratic time types by name when sorting that column', function () {
        actingAsSuperAdminForDemocraticTimeTypeResource();

        $types = DemocraticTimeType::factory()
            ->count(3)
            ->sequence(
                ['name' => 'Bravo Board'],
                ['name' => 'Charlie Committee'],
                ['name' => 'Alpha Assembly'],
            )
            ->create();

        Livewire::test(ListDemocraticTimeTypes::class)
            ->sortTable('name')
            ->assertCanSeeTableRecords($types->sortBy('name'), inOrder: true)
            ->sortTable('name', 'desc')
            ->assertCanSeeTableRecords($types->sortByDesc('name'), inOrder: true);
    });
});

describe('CreateDemocraticTimeType', function () {
    it('renders the page', function () {
        actingAsSuperAdminForDemocraticTimeTypeResource();

        Livewire::test(CreateDemocraticTimeType::class)
            ->assertOk();
    });

    it('creates a democratic time type from valid form data', function () {
        actingAsSuperAdminForDemocraticTimeTypeResource();

        $name = fake()->unique()->words(3, true);
        $acronym = fake()->unique()->lexify('???');

        Livewire::test(CreateDemocraticTimeType::class)
            ->fillForm([
                'name' => $name,
                'acronym' => $acronym,
                'convocation_interval_days' => 30,
                'calculate_quorum_per_college' => false,
                'quorum_percentage' => 50,
                'include_proxies_in_quorum' => true,
            ])
            ->call('create')
            ->assertNotified()
            ->assertRedirect();

        assertDatabaseHas(DemocraticTimeType::class, [
            'name' => $name,
            'acronym' => $acronym,
            'convocation_interval_days' => 30,
        ]);
    });

    it('rejects invalid form data on creation', function (array $data, array $errors) {
        actingAsSuperAdminForDemocraticTimeTypeResource();

        Livewire::test(CreateDemocraticTimeType::class)
            ->fillForm([
                'name' => fake()->unique()->words(3, true),
                'acronym' => fake()->unique()->lexify('???'),
                'convocation_interval_days' => 30,
                'calculate_quorum_per_college' => false,
                'quorum_percentage' => 50,
                'include_proxies_in_quorum' => true,
                ...$data,
            ])
            ->call('create')
            ->assertHasFormErrors($errors)
            ->assertNotNotified()
            ->assertNoRedirect();
    })->with([
        '`name` is required' => [['name' => null], ['name' => 'required']],
        '`acronym` is required' => [['acronym' => null], ['acronym' => 'required']],
        '`convocation_interval_days` is required' => [['convocation_interval_days' => null], ['convocation_interval_days' => 'required']],
        '`convocation_interval_days` must be numeric' => [['convocation_interval_days' => 'invalid'], ['convocation_interval_days' => 'numeric']],
        '`convocation_interval_days` must be at least 1' => [['convocation_interval_days' => 0], ['convocation_interval_days' => 'min']],
        '`quorum_percentage` is required' => [['quorum_percentage' => null], ['quorum_percentage' => 'required']],
        '`quorum_percentage` must be numeric' => [['quorum_percentage' => 'invalid'], ['quorum_percentage' => 'numeric']],
    ]);

    it('shows the translated required message for name', function () {
        actingAsSuperAdminForDemocraticTimeTypeResource();

        Livewire::test(CreateDemocraticTimeType::class)
            ->fillForm(['acronym' => fake()->unique()->lexify('???'), 'convocation_interval_days' => 30, 'calculate_quorum_per_college' => false, 'quorum_percentage' => 50, 'include_proxies_in_quorum' => true, 'name' => null])
            ->call('create')
            ->assertHasFormErrors(['name' => [
                __('validation.required', ['attribute' => Str::lower(__('democratic_time_type.fields.name'))]),
            ]]);
    });
});

describe('EditDemocraticTimeType', function () {
    it('renders the page', function () {
        actingAsSuperAdminForDemocraticTimeTypeResource();

        $type = DemocraticTimeType::factory()->create();

        Livewire::test(EditDemocraticTimeType::class, [
            'record' => $type->id,
        ])
            ->assertOk()
            ->assertSchemaStateSet([
                'name' => $type->name,
                'acronym' => $type->acronym,
                'convocation_interval_days' => $type->convocation_interval_days,
            ]);
    });

    it('updates a democratic time type from valid form data', function () {
        actingAsSuperAdminForDemocraticTimeTypeResource();

        $type = DemocraticTimeType::factory()->create();
        $newName = fake()->unique()->words(3, true);

        Livewire::test(EditDemocraticTimeType::class, [
            'record' => $type->id,
        ])
            ->fillForm([
                'name' => $newName,
            ])
            ->call('save')
            ->assertNotified();

        assertDatabaseHas(DemocraticTimeType::class, [
            'id' => $type->id,
            'name' => $newName,
        ]);
    });

    it('rejects invalid form data on edit', function (array $data, array $errors) {
        actingAsSuperAdminForDemocraticTimeTypeResource();

        $type = DemocraticTimeType::factory()->create();

        Livewire::test(EditDemocraticTimeType::class, [
            'record' => $type->id,
        ])
            ->fillForm($data)
            ->call('save')
            ->assertHasFormErrors($errors)
            ->assertNotNotified();
    })->with([
        '`name` is required' => [['name' => null], ['name' => 'required']],
        '`acronym` is required' => [['acronym' => null], ['acronym' => 'required']],
        '`convocation_interval_days` is required' => [['convocation_interval_days' => null], ['convocation_interval_days' => 'required']],
        '`quorum_percentage` is required' => [['quorum_percentage' => null], ['quorum_percentage' => 'required']],
    ]);
});

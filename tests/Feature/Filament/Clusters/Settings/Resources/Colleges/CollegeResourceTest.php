<?php

declare(strict_types=1);

use App\Filament\Clusters\Settings\Resources\Colleges\Pages\CreateCollege;
use App\Filament\Clusters\Settings\Resources\Colleges\Pages\EditCollege;
use App\Filament\Clusters\Settings\Resources\Colleges\Pages\ListColleges;
use App\Models\College;
use App\Models\User;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin']);
    $this->actingAsUser = User::factory()->create();
    $this->actingAsUser->assignRole('super_admin');
    $this->actingAs($this->actingAsUser);
});

describe('ListColleges', function () {
    it('can load the page', function () {
        livewire(ListColleges::class)
            ->assertOk();
    });

    it('can list colleges', function () {
        $colleges = College::factory()->count(3)->create();

        livewire(ListColleges::class)
            ->assertCanSeeTableRecords($colleges);
    });

    it('can search colleges by name', function () {
        $colleges = College::factory()->count(3)->create();

        livewire(ListColleges::class)
            ->searchTable($colleges->first()->name)
            ->assertCanSeeTableRecords($colleges->take(1))
            ->assertCanNotSeeTableRecords($colleges->skip(1));
    });

    it('can sort colleges by name', function () {
        $colleges = College::factory()->count(3)->create();

        livewire(ListColleges::class)
            ->sortTable('name')
            ->assertCanSeeTableRecords($colleges->sortBy('name'), inOrder: true)
            ->sortTable('name', 'desc')
            ->assertCanSeeTableRecords($colleges->sortByDesc('name'), inOrder: true);
    });
});

describe('CreateCollege', function () {
    it('can load the page', function () {
        livewire(CreateCollege::class)
            ->assertOk();
    });

    it('can create a college', function () {
        $collegeName = fake()->unique()->word();

        Livewire::test(CreateCollege::class)
            ->fillForm([
                'name' => $collegeName,
                'can_vote' => true,
                'require_same_college_for_proxy' => true,
            ])
            ->call('create')
            ->assertNotified()
            ->assertRedirect();

        assertDatabaseHas(College::class, [
            'name' => $collegeName,
            'can_vote' => true,
        ]);
    });

    it('validates required fields', function (array $data, array $errors) {
        livewire(CreateCollege::class)
            ->fillForm([
                'name' => fake()->unique()->word(),
                'can_vote' => true,
                'require_same_college_for_proxy' => true,
                ...$data,
            ])
            ->call('create')
            ->assertHasFormErrors($errors)
            ->assertNotNotified()
            ->assertNoRedirect();
    })->with([
        '`name` is required' => [['name' => null], ['name' => 'required']],
        '`can_vote` is required' => [['can_vote' => null], ['can_vote' => 'required']],
        '`require_same_college_for_proxy` is required' => [['require_same_college_for_proxy' => null], ['require_same_college_for_proxy' => 'required']],
    ]);

    it('validates name uniqueness', function () {
        $existingCollege = College::factory()->create();

        Livewire::test(CreateCollege::class)
            ->fillForm([
                'name' => $existingCollege->name,
                'can_vote' => true,
                'require_same_college_for_proxy' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'unique'])
            ->assertNotNotified()
            ->assertNoRedirect();
    });
});

describe('EditCollege', function () {
    it('can load the page', function () {
        $college = College::factory()->create();

        Livewire::test(EditCollege::class, [
            'record' => $college->id,
        ])
            ->assertOk()
            ->assertSchemaStateSet([
                'name' => $college->name,
                'can_vote' => $college->can_vote,
            ]);
    });

    it('can update a college', function () {
        $college = College::factory()->create();
        $newName = fake()->unique()->word();

        Livewire::test(EditCollege::class, [
            'record' => $college->id,
        ])
            ->fillForm([
                'name' => $newName,
            ])
            ->call('save')
            ->assertNotified();

        assertDatabaseHas(College::class, [
            'id' => $college->id,
            'name' => $newName,
        ]);
    });

    it('validates the form data', function (array $data, array $errors) {
        $college = College::factory()->create();

        Livewire::test(EditCollege::class, [
            'record' => $college->id,
        ])
            ->fillForm($data)
            ->call('save')
            ->assertHasFormErrors($errors)
            ->assertNotNotified();
    })->with([
        '`name` is required' => [['name' => null], ['name' => 'required']],
        '`can_vote` is required' => [['can_vote' => null], ['can_vote' => 'required']],
        '`require_same_college_for_proxy` is required' => [['require_same_college_for_proxy' => null], ['require_same_college_for_proxy' => 'required']],
    ]);
});

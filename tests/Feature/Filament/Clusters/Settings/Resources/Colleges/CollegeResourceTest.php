<?php

declare(strict_types=1);

use App\Filament\Clusters\Settings\Resources\Colleges\Pages\CreateCollege;
use App\Filament\Clusters\Settings\Resources\Colleges\Pages\EditCollege;
use App\Filament\Clusters\Settings\Resources\Colleges\Pages\ListColleges;
use App\Models\College;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

function actingAsSuperAdminForCollegeResource(): User
{
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole('super_admin');

    actingAs($user);

    return $user;
}

describe('ListColleges', function () {
    it('renders the page', function () {
        actingAsSuperAdminForCollegeResource();

        livewire(ListColleges::class)
            ->assertOk();
    });

    it('lists the existing colleges', function () {
        actingAsSuperAdminForCollegeResource();

        $colleges = College::factory()->count(3)->create();

        livewire(ListColleges::class)
            ->assertCanSeeTableRecords($colleges);
    });

    it('lists only colleges matching the searched name', function () {
        actingAsSuperAdminForCollegeResource();

        $colleges = College::factory()
            ->count(3)
            ->sequence(
                ['name' => 'Bravo College'],
                ['name' => 'Charlie College'],
                ['name' => 'Alpha College'],
            )
            ->create();

        livewire(ListColleges::class)
            ->searchTable($colleges->first()->name)
            ->assertCanSeeTableRecords($colleges->take(1))
            ->assertCanNotSeeTableRecords($colleges->skip(1));
    });

    it('orders colleges by name when sorting that column', function () {
        actingAsSuperAdminForCollegeResource();

        $colleges = College::factory()
            ->count(3)
            ->sequence(
                ['name' => 'Bravo College'],
                ['name' => 'Charlie College'],
                ['name' => 'Alpha College'],
            )
            ->create();

        livewire(ListColleges::class)
            ->sortTable('name')
            ->assertCanSeeTableRecords($colleges->sortBy('name'), inOrder: true)
            ->sortTable('name', 'desc')
            ->assertCanSeeTableRecords($colleges->sortByDesc('name'), inOrder: true);
    });
});

describe('CreateCollege', function () {
    it('renders the page', function () {
        actingAsSuperAdminForCollegeResource();

        livewire(CreateCollege::class)
            ->assertOk();
    });

    it('creates a college from valid form data', function () {
        actingAsSuperAdminForCollegeResource();

        $collegeName = fake()->unique()->word();

        livewire(CreateCollege::class)
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

    it('rejects the form when a required field is missing', function (array $data, array $errors) {
        actingAsSuperAdminForCollegeResource();

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

    it('rejects a college name that already exists', function () {
        actingAsSuperAdminForCollegeResource();

        $existingCollege = College::factory()->create();

        livewire(CreateCollege::class)
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

    it('shows the translated required message for name', function () {
        actingAsSuperAdminForCollegeResource();

        livewire(CreateCollege::class)
            ->fillForm(['can_vote' => true, 'require_same_college_for_proxy' => true, 'name' => null])
            ->call('create')
            ->assertHasFormErrors(['name' => [
                __('validation.required', ['attribute' => Str::lower(__('college.fields.name'))]),
            ]]);
    });

    it('shows the translated unique message for name', function () {
        actingAsSuperAdminForCollegeResource();

        livewire(CreateCollege::class)
            ->fillForm(['can_vote' => true, 'require_same_college_for_proxy' => true, 'name' => College::factory()->create()->name])
            ->call('create')
            ->assertHasFormErrors(['name' => [
                __('validation.unique', ['attribute' => Str::lower(__('college.fields.name'))]),
            ]]);
    });
});

describe('EditCollege', function () {
    it('renders the page', function () {
        actingAsSuperAdminForCollegeResource();

        $college = College::factory()->create();

        livewire(EditCollege::class, [
            'record' => $college->id,
        ])
            ->assertOk()
            ->assertSchemaStateSet([
                'name' => $college->name,
                'can_vote' => $college->can_vote,
            ]);
    });

    it('updates a college from valid form data', function () {
        actingAsSuperAdminForCollegeResource();

        $college = College::factory()->create();
        $newName = fake()->unique()->word();

        livewire(EditCollege::class, [
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

    it('rejects invalid form data on edit', function (array $data, array $errors) {
        actingAsSuperAdminForCollegeResource();

        $college = College::factory()->create();

        livewire(EditCollege::class, [
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

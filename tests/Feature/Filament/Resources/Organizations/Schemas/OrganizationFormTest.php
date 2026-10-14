<?php

declare(strict_types=1);

use App\Filament\Resources\Organizations\Pages\CreateOrganization;
use App\Models\College;
use App\Models\CollegeOrganization;
use App\Models\Organization;
use App\Models\User;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');
    actingAs($superAdmin);
});

test('rejects more seats than the college allows', function () {
    $college = College::factory()->create([
        'max_seats_per_organization' => 5,
        'max_delegates_per_seat' => 3,
    ]);

    livewire(CreateOrganization::class)
        ->fillForm([
            'short_name' => 'Test Org',
            'legal_name' => 'Test Organization Legal Name',
            'collegeAllocations' => [
                ['college_id' => $college->id, 'seats' => 6, 'delegates_per_seat' => 2],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['collegeAllocations.0.seats' => 'max']);

    expect(Organization::query()->count())->toBe(0);
});

test('rejects more delegates per seat than the college allows', function () {
    $college = College::factory()->create([
        'max_seats_per_organization' => 5,
        'max_delegates_per_seat' => 3,
    ]);

    livewire(CreateOrganization::class)
        ->fillForm([
            'short_name' => 'Test Org',
            'legal_name' => 'Test Organization Legal Name',
            'collegeAllocations' => [
                ['college_id' => $college->id, 'seats' => 2, 'delegates_per_seat' => 4],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['collegeAllocations.0.delegates_per_seat' => 'max']);

    expect(Organization::query()->count())->toBe(0);
});

test('creates the organization with seats up to the college limits', function () {
    $college = College::factory()->create([
        'max_seats_per_organization' => 5,
        'max_delegates_per_seat' => 3,
    ]);

    livewire(CreateOrganization::class)
        ->fillForm([
            'short_name' => 'Test Org',
            'legal_name' => 'Test Organization Legal Name',
            'collegeAllocations' => [
                ['college_id' => $college->id, 'seats' => 5, 'delegates_per_seat' => 3],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $organization = Organization::query()->where('short_name', 'Test Org')->sole();
    $collegeOrganization = CollegeOrganization::query()
        ->where('organization_id', $organization->id)
        ->where('college_id', $college->id)
        ->sole();

    expect($collegeOrganization)
        ->seats->toBe(5)
        ->delegates_per_seat->toBe(3);
});

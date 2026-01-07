<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;
use App\Models\Volunteer;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
});

test('user without volunteer organization is redirected to profile page', function () {
    $user = User::factory()->create(['email' => 'test@afges.org']);
    Organization::factory()->create(); // Create at least one organization

    actingAs($user);

    $response = get(route('filament.root.pages.dashboard'));

    $response->assertRedirect(route('filament.root.pages.my-profile'));
});

test('user with approved volunteer organization can access dashboard', function () {
    $user = User::factory()->create(['email' => 'test@afges.org']);
    $organization = Organization::factory()->create();

    Volunteer::factory()->approved()->create([
        'user_id' => $user->id,
        'organization_id' => $organization->id,
    ]);

    actingAs($user);

    $response = get(route('filament.root.pages.dashboard'));

    $response->assertSuccessful();
});

test('user with pending volunteer organization is redirected to profile page', function () {
    $user = User::factory()->create(['email' => 'test@afges.org']);
    $organization = Organization::factory()->create();

    // Create pending volunteer (bypass global scope)
    Volunteer::withoutGlobalScopes()->create(
        Volunteer::factory()->make([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ])->toArray()
    );

    actingAs($user);

    $response = get(route('filament.root.pages.dashboard'));

    $response->assertRedirect(route('filament.root.pages.my-profile'));
});

test('user can select organizations on the profile page', function () {
    $user = User::factory()->create(['email' => 'test@afges.org']);
    $organization1 = Organization::factory()->create(['short_name' => 'Org 1']);
    $organization2 = Organization::factory()->create(['short_name' => 'Org 2']);

    actingAs($user);

    $startDate1 = now()->subDays(7)->startOfDay();
    $startDate2 = now()->subDays(14)->startOfDay();

    Livewire::test(\App\Livewire\Profile\ManageVolunteers::class)
        ->fillForm([
            'organizations' => [
                [
                    'organization_id' => $organization1->id,
                    'started_at' => $startDate1->format('Y-m-d'),
                ],
                [
                    'organization_id' => $organization2->id,
                    'started_at' => $startDate2->format('Y-m-d'),
                ],
            ],
        ])
        ->assertHasNoFormErrors()
        ->call('submitVolunteers')
        ->assertHasNoFormErrors();

    // Need to bypass global scope to find pending volunteers
    $volunteer1 = \App\Models\Volunteer::withoutGlobalScopes()
        ->where('user_id', $user->id)
        ->where('organization_id', $organization1->id)
        ->first();

    $volunteer2 = \App\Models\Volunteer::withoutGlobalScopes()
        ->where('user_id', $user->id)
        ->where('organization_id', $organization2->id)
        ->first();

    expect($volunteer1)->not->toBeNull();
    expect($volunteer2)->not->toBeNull();
    expect($volunteer1->started_at->format('Y-m-d'))->toBe($startDate1->format('Y-m-d'));
    expect($volunteer2->started_at->format('Y-m-d'))->toBe($startDate2->format('Y-m-d'));
    expect($volunteer1->isPending())->toBeTrue();
    expect($volunteer2->isPending())->toBeTrue();
});

test('user must select at least one organization', function () {
    $user = User::factory()->create(['email' => 'test@afges.org']);
    Organization::factory()->create(['short_name' => 'Org 1']);

    actingAs($user);

    Livewire::test(\App\Livewire\Profile\ManageVolunteers::class)
        ->fillForm([
            'organizations' => [],
        ])
        ->call('submitVolunteers');

    // Submitting empty organizations should not create any volunteers
    expect(Volunteer::withoutGlobalScopes()->where('user_id', $user->id)->count())->toBe(0);
});

test('user without volunteer organization can access dashboard when no organizations exist', function () {
    $user = User::factory()->create(['email' => 'test@afges.org']);

    actingAs($user);

    $response = get(route('filament.root.pages.dashboard'));

    $response->assertSuccessful();
});

test('user without approved volunteer can access logout route', function () {
    $user = User::factory()->create(['email' => 'test@afges.org']);
    Organization::factory()->create(); // Create at least one organization

    actingAs($user);

    // User should be able to logout even without approved volunteers
    $response = $this->post(route('filament.root.auth.logout'));

    $response->assertRedirect();
    $this->assertGuest();
});

test('super admin can bypass organization selection requirement', function () {
    // Create the super_admin role
    \Spatie\Permission\Models\Role::create(['name' => 'super_admin']);

    $superAdmin = User::factory()->create(['email' => 'admin@afges.org']);
    $superAdmin->assignRole('super_admin');
    Organization::factory()->create(); // Create at least one organization

    actingAs($superAdmin);

    // Super admin should be able to access dashboard without any volunteer organizations
    $response = get(route('filament.root.pages.dashboard'));

    $response->assertSuccessful();
});

test('user can apply as delegate when selecting organizations', function () {
    $user = User::factory()->create(['email' => 'test@afges.org']);
    $organization = Organization::factory()->create(['short_name' => 'Test Org']);

    // Create college and seat for the organization
    $college = \App\Models\College::factory()->create(['name' => 'Test College']);
    $collegeOrganization = \App\Models\CollegeOrganization::factory()->create([
        'college_id' => $college->id,
        'organization_id' => $organization->id,
        'delegates_per_seat' => 2,
    ]);
    $seat = \App\Models\Seat::factory()->create([
        'college_organization_id' => $collegeOrganization->id,
        'seat_number' => 1,
    ]);

    actingAs($user);

    $startDate = now()->subDays(7)->startOfDay();

    // First submit volunteer application
    Livewire::test(\App\Livewire\Profile\ManageVolunteers::class)
        ->fillForm([
            'organizations' => [
                [
                    'organization_id' => $organization->id,
                    'started_at' => $startDate->format('Y-m-d'),
                    'position_title' => 'Membre',
                ],
            ],
        ])
        ->assertHasNoFormErrors()
        ->call('submitVolunteers')
        ->assertHasNoFormErrors();

    // Then submit delegate application
    Livewire::test(\App\Livewire\Profile\ManageDelegates::class)
        ->fillForm([
            'applications' => [
                [
                    'organization_id' => $organization->id,
                    'seat_id' => $seat->id,
                    'email' => $user->email,
                    'started_at' => $startDate->format('Y-m-d'),
                ],
            ],
        ])
        ->assertHasNoFormErrors()
        ->call('submitDelegates')
        ->assertHasNoFormErrors();

    // Verify volunteer application was created
    $volunteer = Volunteer::withoutGlobalScopes()
        ->where('user_id', $user->id)
        ->where('organization_id', $organization->id)
        ->first();

    expect($volunteer)->not->toBeNull();
    expect($volunteer->isPending())->toBeTrue();

    // Verify delegate application was created
    $delegate = \App\Models\Delegate::withoutGlobalScopes()
        ->where('user_id', $user->id)
        ->where('organization_id', $organization->id)
        ->where('seat_id', $seat->id)
        ->first();

    expect($delegate)->not->toBeNull();
    expect($delegate->isPending())->toBeTrue();
});

test('user can apply to multiple organizations with different delegate options', function () {
    $user = User::factory()->create(['email' => 'test@afges.org']);
    $organization1 = Organization::factory()->create(['short_name' => 'Org 1']);
    $organization2 = Organization::factory()->create(['short_name' => 'Org 2']);

    // Create college and seats
    $college = \App\Models\College::factory()->create(['name' => 'Test College']);
    $collegeOrg1 = \App\Models\CollegeOrganization::factory()->create([
        'college_id' => $college->id,
        'organization_id' => $organization1->id,
        'delegates_per_seat' => 2,
    ]);
    $seat1 = \App\Models\Seat::factory()->create([
        'college_organization_id' => $collegeOrg1->id,
        'seat_number' => 1,
    ]);

    actingAs($user);

    $startDate1 = now()->subDays(7)->startOfDay();
    $startDate2 = now()->subDays(14)->startOfDay();

    // Submit volunteer applications for both organizations
    Livewire::test(\App\Livewire\Profile\ManageVolunteers::class)
        ->fillForm([
            'organizations' => [
                [
                    'organization_id' => $organization1->id,
                    'started_at' => $startDate1->format('Y-m-d'),
                ],
                [
                    'organization_id' => $organization2->id,
                    'started_at' => $startDate2->format('Y-m-d'),
                ],
            ],
        ])
        ->assertHasNoFormErrors()
        ->call('submitVolunteers')
        ->assertHasNoFormErrors();

    // Submit delegate application for only organization1
    Livewire::test(\App\Livewire\Profile\ManageDelegates::class)
        ->fillForm([
            'applications' => [
                [
                    'organization_id' => $organization1->id,
                    'seat_id' => $seat1->id,
                    'email' => $user->email,
                    'started_at' => $startDate1->format('Y-m-d'),
                ],
            ],
        ])
        ->assertHasNoFormErrors()
        ->call('submitDelegates')
        ->assertHasNoFormErrors();

    // Verify both volunteer applications
    expect(Volunteer::withoutGlobalScopes()->where('user_id', $user->id)->count())->toBe(2);

    // Verify only one delegate application (for organization1)
    $delegates = \App\Models\Delegate::withoutGlobalScopes()
        ->where('user_id', $user->id)
        ->get();

    expect($delegates)->toHaveCount(1);
    expect($delegates->first()->organization_id)->toBe($organization1->id);
    expect($delegates->first()->seat_id)->toBe($seat1->id);
});

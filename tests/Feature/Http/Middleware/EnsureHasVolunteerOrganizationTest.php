<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;
use App\Models\Volunteer;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
});

test('redirects to the profile page when the user has no volunteer organization', function () {
    $user = User::factory()->create(['email' => 'volunteer@afges.org']);
    Organization::factory()->create();

    actingAs($user);

    get(route('filament.root.pages.dashboard'))
        ->assertRedirect(route('filament.root.pages.my-profile'));
});

test('redirects to the profile page when the volunteer organization is pending', function () {
    $user = User::factory()->create(['email' => 'volunteer@afges.org']);
    $organization = Organization::factory()->create();
    Volunteer::factory()->pending()->create([
        'user_id' => $user->id,
        'organization_id' => $organization->id,
    ]);

    actingAs($user);

    get(route('filament.root.pages.dashboard'))
        ->assertRedirect(route('filament.root.pages.my-profile'));
});

test('redirects to the profile page when the volunteer organization is rejected', function () {
    $user = User::factory()->create(['email' => 'volunteer@afges.org']);
    $organization = Organization::factory()->create();
    Volunteer::factory()->rejected()->create([
        'user_id' => $user->id,
        'organization_id' => $organization->id,
    ]);

    actingAs($user);

    get(route('filament.root.pages.dashboard'))
        ->assertRedirect(route('filament.root.pages.my-profile'));
});

test('lets a user with an approved volunteer organization reach the dashboard', function () {
    $user = User::factory()->create(['email' => 'volunteer@afges.org']);
    $organization = Organization::factory()->create();
    Volunteer::factory()->approved()->create([
        'user_id' => $user->id,
        'organization_id' => $organization->id,
    ]);

    actingAs($user);

    get(route('filament.root.pages.dashboard'))->assertSuccessful();
});

test('lets any user reach the dashboard when no organization exists', function () {
    $user = User::factory()->create(['email' => 'volunteer@afges.org']);

    actingAs($user);

    get(route('filament.root.pages.dashboard'))->assertSuccessful();
});

test('does not redirect a user without approved volunteer away from the profile page', function () {
    $user = User::factory()->create(['email' => 'volunteer@afges.org']);
    Organization::factory()->create();

    actingAs($user);

    get(route('filament.root.pages.my-profile'))->assertSuccessful();
});

test('lets a user without approved volunteer log out', function () {
    $user = User::factory()->create(['email' => 'volunteer@afges.org']);
    Organization::factory()->create();

    actingAs($user);

    post(route('filament.root.auth.logout'))->assertRedirect();

    $this->assertGuest();
});

test('lets a super admin reach the dashboard without a volunteer organization', function () {
    Role::create(['name' => 'super_admin']);
    $superAdmin = User::factory()->create(['email' => 'volunteer@afges.org']);
    $superAdmin->assignRole('super_admin');
    Organization::factory()->create();

    actingAs($superAdmin);

    get(route('filament.root.pages.dashboard'))->assertSuccessful();
});

test('redirects to the profile page when an approved volunteer has no position title', function () {
    $user = User::factory()->create(['email' => 'volunteer@afges.org']);
    Volunteer::factory()->approved()->for($user)->create(['position_title' => null]);

    actingAs($user);

    get(route('filament.root.pages.dashboard'))
        ->assertRedirect(route('filament.root.pages.my-profile'));
});

test('lets a super admin reach the dashboard without a position title', function () {
    Role::create(['name' => 'super_admin']);
    $superAdmin = User::factory()->create(['email' => 'volunteer@afges.org']);
    $superAdmin->assignRole('super_admin');
    Volunteer::factory()->approved()->for($superAdmin)->create(['position_title' => null]);

    actingAs($superAdmin);

    get(route('filament.root.pages.dashboard'))->assertSuccessful();
});

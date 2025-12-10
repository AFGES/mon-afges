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

test('user without volunteer organization is redirected to select organization page', function () {
    $user = User::factory()->create(['email' => 'test@afges.org']);
    Organization::factory()->create(); // Create at least one organization

    actingAs($user);

    $response = get(route('filament.admin.pages.dashboard'));

    $response->assertRedirect(route('filament.admin.pages.select-organization'));
});

test('user with volunteer organization can access dashboard', function () {
    $user = User::factory()->create(['email' => 'test@afges.org']);
    $organization = Organization::factory()->create();

    Volunteer::factory()->create([
        'user_id' => $user->id,
        'organization_id' => $organization->id,
    ]);

    actingAs($user);

    $response = get(route('filament.admin.pages.dashboard'));

    $response->assertSuccessful();
});

test('user can select organizations on the select organization page', function () {
    $user = User::factory()->create(['email' => 'test@afges.org']);
    $organization1 = Organization::factory()->create(['short_name' => 'Org 1']);
    $organization2 = Organization::factory()->create(['short_name' => 'Org 2']);

    actingAs($user);

    $startDate1 = now()->subDays(7)->startOfDay();
    $startDate2 = now()->subDays(14)->startOfDay();

    Livewire::test(\App\Filament\Pages\SelectOrganization::class)
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
        ->call('submit')
        ->assertHasNoFormErrors()
        ->assertRedirect(route('filament.admin.pages.dashboard'));

    $volunteer1 = \App\Models\Volunteer::where('user_id', $user->id)
        ->where('organization_id', $organization1->id)
        ->first();

    $volunteer2 = \App\Models\Volunteer::where('user_id', $user->id)
        ->where('organization_id', $organization2->id)
        ->first();

    expect($volunteer1)->not->toBeNull();
    expect($volunteer2)->not->toBeNull();
    expect($volunteer1->started_at->format('Y-m-d'))->toBe($startDate1->format('Y-m-d'));
    expect($volunteer2->started_at->format('Y-m-d'))->toBe($startDate2->format('Y-m-d'));
});

test('user must select at least one organization', function () {
    $user = User::factory()->create(['email' => 'test@afges.org']);
    Organization::factory()->create(['short_name' => 'Org 1']);

    actingAs($user);

    Livewire::test(\App\Filament\Pages\SelectOrganization::class)
        ->fillForm([
            'organizations' => [],
        ])
        ->call('submit')
        ->assertHasFormErrors(['organizations']);
});

test('user without volunteer organization can access dashboard when no organizations exist', function () {
    $user = User::factory()->create(['email' => 'test@afges.org']);

    actingAs($user);

    $response = get(route('filament.admin.pages.dashboard'));

    $response->assertSuccessful();
});

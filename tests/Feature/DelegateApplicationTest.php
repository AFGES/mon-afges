<?php

declare(strict_types=1);

use App\Enums\ApprovalStatus;
use App\Filament\Pages\ApplyAsDelegate;
use App\Models\College;
use App\Models\CollegeOrganization;
use App\Models\Delegate;
use App\Models\Organization;
use App\Models\Seat;
use App\Models\User;
use App\Models\Volunteer;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a test user
    $this->user = User::factory()->create();

    // Create organizations and seats
    $this->organization = Organization::factory()->create(['short_name' => 'TEST ORG']);
    $this->college = College::factory()->create(['name' => 'Test College']);

    $this->collegeOrganization = CollegeOrganization::factory()
        ->for($this->college)
        ->for($this->organization)
        ->withSeats(3)
        ->create();

    $this->seat = $this->collegeOrganization->seatRecords->first();
});

test('unauthenticated users are redirected from delegate application page', function () {
    // Skip this test - Filament handles authentication at the panel level
})->skip();

test('authenticated users can access delegate application page', function () {
    $this->actingAs($this->user);

    Livewire::test(ApplyAsDelegate::class)
        ->assertSuccessful();
});

test('users without volunteer relationships see warning notification', function () {
    $this->actingAs($this->user);

    Livewire::test(ApplyAsDelegate::class)
        ->assertNotified();
});

test('users with volunteer relationships can see organizations in form', function () {
    // Create a volunteer relationship
    Volunteer::factory()
        ->for($this->user)
        ->for($this->organization)
        ->create();

    $this->actingAs($this->user);

    Livewire::test(ApplyAsDelegate::class)
        ->assertFormFieldExists('organization_id');
});

test('seat field is disabled when no organization is selected', function () {
    Volunteer::factory()
        ->for($this->user)
        ->for($this->organization)
        ->create();

    $this->actingAs($this->user);

    Livewire::test(ApplyAsDelegate::class)
        ->assertFormFieldIsDisabled('seat_id');
});

test('seat field is enabled when organization is selected', function () {
    Volunteer::factory()
        ->for($this->user)
        ->for($this->organization)
        ->create();

    $this->actingAs($this->user);

    Livewire::test(ApplyAsDelegate::class)
        ->fillForm([
            'organization_id' => $this->organization->id,
        ])
        ->assertFormFieldIsEnabled('seat_id');
});

test('user can submit delegate application successfully', function () {
    Volunteer::factory()
        ->for($this->user)
        ->for($this->organization)
        ->create();

    $this->actingAs($this->user);

    Livewire::test(ApplyAsDelegate::class)
        ->fillForm([
            'organization_id' => $this->organization->id,
            'seat_id' => $this->seat->id,
        ])
        ->call('submit')
        ->assertNotified()
        ->assertHasNoFormErrors();

    // Verify delegate was created
    $this->assertDatabaseHas('delegates', [
        'user_id' => $this->user->id,
        'organization_id' => $this->organization->id,
        'seat_id' => $this->seat->id,
        'status' => ApprovalStatus::Pending->value,
    ]);
});

test('organization_id is required', function () {
    Volunteer::factory()
        ->for($this->user)
        ->for($this->organization)
        ->create();

    $this->actingAs($this->user);

    Livewire::test(ApplyAsDelegate::class)
        ->fillForm([
            'organization_id' => null,
            'seat_id' => $this->seat->id,
        ])
        ->call('submit')
        ->assertHasFormErrors(['organization_id' => 'required']);
});

test('seat_id is required', function () {
    Volunteer::factory()
        ->for($this->user)
        ->for($this->organization)
        ->create();

    $this->actingAs($this->user);

    Livewire::test(ApplyAsDelegate::class)
        ->fillForm([
            'organization_id' => $this->organization->id,
            'seat_id' => null,
        ])
        ->call('submit')
        ->assertHasFormErrors(['seat_id' => 'required']);
});

test('user cannot apply for the same seat twice', function () {
    Volunteer::factory()
        ->for($this->user)
        ->for($this->organization)
        ->create();

    // Create existing delegate for this user and seat
    Delegate::factory()
        ->for($this->user)
        ->for($this->organization)
        ->create([
            'seat_id' => $this->seat->id,
            'started_at' => now(),
            'ended_at' => null,
            'status' => ApprovalStatus::Pending,
        ]);

    $this->actingAs($this->user);

    Livewire::test(ApplyAsDelegate::class)
        ->fillForm([
            'organization_id' => $this->organization->id,
            'seat_id' => $this->seat->id,
        ])
        ->call('submit')
        ->assertNotified();

    // Verify no new delegate was created (still only 1)
    expect(Delegate::withoutGlobalScopes()->where('user_id', $this->user->id)->count())->toBe(1);
});

test('user can apply for different seats', function () {
    Volunteer::factory()
        ->for($this->user)
        ->for($this->organization)
        ->create();

    $seat2 = $this->collegeOrganization->seatRecords->skip(1)->first();

    $this->actingAs($this->user);

    // Apply for first seat
    Livewire::test(ApplyAsDelegate::class)
        ->fillForm([
            'organization_id' => $this->organization->id,
            'seat_id' => $this->seat->id,
        ])
        ->call('submit')
        ->assertNotified();

    // Apply for second seat
    Livewire::test(ApplyAsDelegate::class)
        ->fillForm([
            'organization_id' => $this->organization->id,
            'seat_id' => $seat2->id,
        ])
        ->call('submit')
        ->assertNotified();

    // Verify two delegates were created
    expect(Delegate::withoutGlobalScopes()->where('user_id', $this->user->id)->count())->toBe(2);
});

test('user can only see organizations where they volunteer', function () {
    $organization2 = Organization::factory()->create(['short_name' => 'OTHER ORG']);

    // Create volunteer relationship only with first organization
    Volunteer::factory()
        ->for($this->user)
        ->for($this->organization)
        ->create();

    $this->actingAs($this->user);

    $component = Livewire::test(ApplyAsDelegate::class);

    // Get the options for organization_id field
    $organizationOptions = $component->instance()->getVolunteerOrganizations();

    expect($organizationOptions)->toHaveKey($this->organization->id)
        ->and($organizationOptions)->not->toHaveKey($organization2->id);
});

test('seats are filtered by selected organization', function () {
    $organization2 = Organization::factory()->create(['short_name' => 'ORG 2']);
    $collegeOrg2 = CollegeOrganization::factory()
        ->for($this->college)
        ->for($organization2)
        ->withSeats(2)
        ->create();

    // Create volunteer relationships with both organizations
    Volunteer::factory()
        ->for($this->user)
        ->for($this->organization)
        ->create();

    Volunteer::factory()
        ->for($this->user)
        ->for($organization2)
        ->create();

    $this->actingAs($this->user);

    $component = Livewire::test(ApplyAsDelegate::class)
        ->fillForm([
            'organization_id' => $this->organization->id,
        ]);

    // Get available seats for the selected organization
    $availableSeats = $component->instance()->getAvailableSeats($this->organization->id);

    // Verify seats belong to the correct organization
    $seatIds = array_keys($availableSeats);
    $seats = Seat::whereIn('id', $seatIds)->get();

    foreach ($seats as $seat) {
        expect($seat->collegeOrganization->organization_id)->toBe($this->organization->id);
    }
});

test('delegate application includes started_at timestamp', function () {
    Volunteer::factory()
        ->for($this->user)
        ->for($this->organization)
        ->create();

    $this->actingAs($this->user);

    Livewire::test(ApplyAsDelegate::class)
        ->fillForm([
            'organization_id' => $this->organization->id,
            'seat_id' => $this->seat->id,
        ])
        ->call('submit');

    $delegate = Delegate::withoutGlobalScopes()
        ->where('user_id', $this->user->id)
        ->first();

    expect($delegate->started_at)->not->toBeNull()
        ->and($delegate->started_at->isToday())->toBeTrue()
        ->and($delegate->ended_at)->toBeNull()
        ->and($delegate->status)->toBe(ApprovalStatus::Pending);
});

test('form resets seat when organization changes', function () {
    $organization2 = Organization::factory()->create();
    $collegeOrg2 = CollegeOrganization::factory()
        ->for($this->college)
        ->for($organization2)
        ->withSeats(1)
        ->create();

    Volunteer::factory()->for($this->user)->for($this->organization)->create();
    Volunteer::factory()->for($this->user)->for($organization2)->create();

    $this->actingAs($this->user);

    Livewire::test(ApplyAsDelegate::class)
        ->fillForm([
            'organization_id' => $this->organization->id,
            'seat_id' => $this->seat->id,
        ])
        ->fillForm([
            'organization_id' => $organization2->id,
        ])
        ->assertFormSet([
            'seat_id' => null,
        ]);
});

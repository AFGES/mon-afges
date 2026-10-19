<?php

declare(strict_types=1);

use App\Filament\Pages\JoinOrganization;
use App\Models\Organization;
use App\Models\User;
use App\Models\Volunteer;
use Filament\Facades\Filament;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

beforeEach(function () {
    Filament::setCurrentPanel('root');
});

test('redirects guests to the login page', function () {
    get(route('filament.root.join-organization'))
        ->assertRedirect(route('filament.root.auth.login'));
});

test('shows the request form to a user without any volunteer', function () {
    Organization::factory()->create(['short_name' => 'AFGES']);
    actingAs(User::factory()->create());

    livewire(JoinOrganization::class)
        ->assertSuccessful()
        ->assertSee(__('join_organization.request.subheading'))
        ->assertSee(__('join_organization.logout'));
});

test('shows the request form again when the only request was rejected', function () {
    $user = User::factory()->create();
    Volunteer::factory()->rejected()->for($user)->create();
    actingAs($user);

    livewire(JoinOrganization::class)
        ->assertSee(__('join_organization.request.subheading'))
        ->assertDontSee(__('join_organization.pending.subheading'));
});

test('creates a pending volunteer and shows the waiting screen after a request', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create(['short_name' => 'AFGES']);
    actingAs($user);

    livewire(JoinOrganization::class)
        ->fillForm([
            'organization_id' => $organization->id,
            'position_title' => 'Trésorier',
            'started_at' => now()->subDays(3)->toDateString(),
        ])
        ->call('submitRequest')
        ->assertHasNoFormErrors()
        ->assertNotified(__('volunteer.notifications.pending_approval.title'))
        ->assertSee(__('join_organization.pending.message', ['organization' => 'AFGES']));

    $volunteer = Volunteer::withoutGlobalScopes()->where('user_id', $user->id)->sole();

    expect($volunteer->organization_id)->toBe($organization->id)
        ->and($volunteer->position_title)->toBe('Trésorier')
        ->and($volunteer->started_at->toDateString())->toBe(now()->subDays(3)->toDateString())
        ->and($volunteer->isPending())->toBeTrue();
});

test('validates the request form', function () {
    Organization::factory()->create();
    actingAs(User::factory()->create());

    livewire(JoinOrganization::class)
        ->fillForm([
            'organization_id' => null,
            'position_title' => str_repeat('a', 256),
            'started_at' => now()->addDay()->toDateString(),
        ])
        ->call('submitRequest')
        ->assertHasFormErrors(['organization_id' => 'required', 'position_title' => 'max', 'started_at']);

    expect(Volunteer::withoutGlobalScopes()->count())->toBe(0);
});

test('shows the waiting screen without a form while a request is pending', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create(['short_name' => 'AFGES']);
    Volunteer::factory()->pending()->for($user)->for($organization)->create();
    actingAs($user);

    livewire(JoinOrganization::class)
        ->assertSee(__('join_organization.pending.message', ['organization' => 'AFGES']))
        ->assertDontSee(__('join_organization.request.subheading'))
        ->assertDontSee(__('profile.form.submit.label'));
});

test('enforces the one-new-organization lock when a request was created in the meantime', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create(['short_name' => 'AFGES']);
    actingAs($user);

    $component = livewire(JoinOrganization::class);

    Volunteer::factory()->pending()->for($user)->for($otherOrganization)->create();

    $component
        ->fillForm([
            'organization_id' => $organization->id,
            'position_title' => 'Trésorier',
        ])
        ->call('submitRequest')
        ->assertHasErrors([
            'data.organization_id' => [__('profile.manage_volunteers.request_locked.message', ['organization' => 'AFGES'])],
        ]);

    expect(Volunteer::withoutGlobalScopes()->where('user_id', $user->id)->count())->toBe(1);
});

test('saves the position titles and redirects to the dashboard', function () {
    $user = User::factory()->create();
    $volunteer = Volunteer::factory()->approved()->for($user)->create(['position_title' => null]);
    actingAs($user);

    livewire(JoinOrganization::class)
        ->assertSee(__('profile.manage_volunteers.position_title_required.banner'))
        ->set('positionTitles.'.$volunteer->id, 'Secrétaire')
        ->call('definePositionTitles')
        ->assertHasNoErrors()
        ->assertNotified(__('profile.manage_volunteers.position_title_required.success_title'))
        ->assertRedirect(Filament::getUrl());

    expect($volunteer->refresh()->position_title)->toBe('Secrétaire');
});

test('requires a position title', function () {
    $user = User::factory()->create();
    $volunteer = Volunteer::factory()->approved()->for($user)->create(['position_title' => null]);
    actingAs($user);

    livewire(JoinOrganization::class)
        ->set('positionTitles.'.$volunteer->id, '')
        ->call('definePositionTitles')
        ->assertHasErrors(['positionTitles.'.$volunteer->id => 'required']);

    expect($volunteer->refresh()->position_title)->toBeNull();
});

test('redirects away a user who does not need onboarding', function () {
    $user = User::factory()->create();
    Volunteer::factory()->approved()->for($user)->create(['position_title' => 'Membre']);
    actingAs($user);

    livewire(JoinOrganization::class)->assertRedirect(Filament::getUrl());
});

test('redirects away when no organization exists', function () {
    actingAs(User::factory()->create());

    livewire(JoinOrganization::class)->assertRedirect(Filament::getUrl());
});

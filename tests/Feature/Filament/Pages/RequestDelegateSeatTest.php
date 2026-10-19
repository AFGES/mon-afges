<?php

declare(strict_types=1);

use App\Filament\Pages\RequestDelegateSeat;
use App\Models\CollegeOrganization;
use App\Models\Delegate;
use App\Models\Organization;
use App\Models\Seat;
use App\Models\User;
use App\Models\Volunteer;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\freezeTime;
use function Pest\Livewire\livewire;

/**
 * @return array{0: User, 1: CollegeOrganization, 2: Seat}
 */
function eligibleDelegateCandidate(): array
{
    $user = User::factory()->create(['first_name' => 'Camille', 'last_name' => 'Durand', 'email' => 'camille@afges.org']);
    $collegeOrganization = CollegeOrganization::factory()->withSeats(1)->create(['delegates_per_seat' => 2]);
    Volunteer::factory()->approved()->create([
        'user_id' => $user->id,
        'organization_id' => $collegeOrganization->organization_id,
        'ended_at' => null,
    ]);

    return [$user, $collegeOrganization, $collegeOrganization->seatRecords()->firstOrFail()];
}

/**
 * @return array<string, mixed>
 */
function validSeatRequestData(User $user, CollegeOrganization $collegeOrganization, Seat $seat): array
{
    return [
        'organization_id' => $collegeOrganization->organization_id,
        'seat_id' => $seat->id,
        'email' => $user->email,
        'started_at' => now()->subDays(7)->toDateString(),
        'last_name' => 'Durand',
        'first_name' => 'Camille',
        'study_program' => 'Master 2 Droit',
        'birth_date' => '2000-01-31',
        'phone' => '0601020304',
        'address_street' => '1 rue de la Paix',
        'address_complement' => 'Bâtiment B',
        'address_postcode' => '75002',
        'address_city' => 'Paris',
        'elected_mandates' => 'CFVU',
    ];
}

it('lets an approved member of an organization with seats access the page without a navigation item', function () {
    [$user] = eligibleDelegateCandidate();

    actingAs($user);

    expect(RequestDelegateSeat::canAccess())->toBeTrue()
        ->and(RequestDelegateSeat::shouldRegisterNavigation())->toBeFalse();

    $this->get(RequestDelegateSeat::getUrl())->assertOk();
});

it('forbids access while a request is pending', function () {
    [$user] = eligibleDelegateCandidate();
    Delegate::factory()->pending()->create(['user_id' => $user->id]);

    actingAs($user);

    expect(RequestDelegateSeat::canAccess())->toBeFalse();

    $this->get(RequestDelegateSeat::getUrl())->assertForbidden();
});

it('forbids access', function (Closure $setUp) {
    $user = User::factory()->create(['email' => 'member@afges.org']);
    $setUp($user);

    actingAs($user);

    expect(RequestDelegateSeat::canAccess())->toBeFalse()
        ->and(RequestDelegateSeat::shouldRegisterNavigation())->toBeFalse();

    $this->get(RequestDelegateSeat::getUrl())->assertForbidden();
})->with([
    'without volunteer row' => fn () => fn (User $user) => CollegeOrganization::factory()->withSeats(1)->create(),
    'pending volunteer' => fn () => function (User $user): void {
        $collegeOrganization = CollegeOrganization::factory()->withSeats(1)->create();
        Volunteer::factory()->pending()->create(['user_id' => $user->id, 'organization_id' => $collegeOrganization->organization_id]);
    },
    'rejected volunteer' => fn () => function (User $user): void {
        $collegeOrganization = CollegeOrganization::factory()->withSeats(1)->create();
        Volunteer::factory()->rejected()->create(['user_id' => $user->id, 'organization_id' => $collegeOrganization->organization_id]);
    },
    'ended volunteer' => fn () => function (User $user): void {
        $collegeOrganization = CollegeOrganization::factory()->withSeats(1)->create();
        Volunteer::factory()->approved()->create([
            'user_id' => $user->id,
            'organization_id' => $collegeOrganization->organization_id,
            'ended_at' => now()->subDay(),
        ]);
    },
    'organization without seats' => fn () => function (User $user): void {
        $collegeOrganization = CollegeOrganization::factory()->create();
        Volunteer::factory()->approved()->create(['user_id' => $user->id, 'organization_id' => $collegeOrganization->organization_id, 'ended_at' => null]);
    },
]);

it('only offers eligible organizations and preselects the single one', function () {
    [$user, $collegeOrganization] = eligibleDelegateCandidate();
    $other = Organization::factory()->create();

    actingAs($user);

    livewire(RequestDelegateSeat::class)
        ->assertFormSet(['organization_id' => $collegeOrganization->organization_id])
        ->assertFormFieldExists('organization_id', fn ($field): bool => array_keys($field->getOptions()) === [$collegeOrganization->organization_id]
            && ! array_key_exists($other->id, $field->getOptions()));
});

it('pre-fills the personal details of the user', function () {
    [$user] = eligibleDelegateCandidate();
    $user->update(['phone' => '0611223344', 'study_program' => 'L3 Histoire']);

    actingAs($user);

    livewire(RequestDelegateSeat::class)
        ->assertFormSet([
            'first_name' => 'Camille',
            'last_name' => 'Durand',
            'phone' => '+33611223344',
            'study_program' => 'L3 Histoire',
            'email' => $user->email,
        ]);
});

it('rejects an organization the user is not eligible for', function () {
    mockGoogleWorkspaceService()->shouldReceive('addToGroup')->zeroOrMoreTimes()->andReturnTrue();
    [$user] = eligibleDelegateCandidate();
    $forged = CollegeOrganization::factory()->withSeats(1)->create(['delegates_per_seat' => 2]);

    actingAs($user);

    livewire(RequestDelegateSeat::class)
        ->fillForm(validSeatRequestData($user, $forged, $forged->seatRecords()->firstOrFail()))
        ->call('submit')
        ->assertHasFormErrors(['organization_id']);

    expect(Delegate::withoutGlobalScopes()->count())->toBe(0);
});

it('requires', function (string $field) {
    [$user, $collegeOrganization, $seat] = eligibleDelegateCandidate();

    actingAs($user);

    livewire(RequestDelegateSeat::class)
        ->fillForm([...validSeatRequestData($user, $collegeOrganization, $seat), $field => null])
        ->call('submit')
        ->assertHasFormErrors([$field => 'required']);

    expect(Delegate::withoutGlobalScopes()->count())->toBe(0);
})->with(['study_program', 'birth_date', 'phone', 'address_street', 'address_postcode', 'address_city', 'first_name', 'last_name', 'seat_id']);

it('rejects an invalid phone number', function () {
    [$user, $collegeOrganization, $seat] = eligibleDelegateCandidate();

    actingAs($user);

    livewire(RequestDelegateSeat::class)
        ->fillForm([...validSeatRequestData($user, $collegeOrganization, $seat), 'phone' => '123'])
        ->call('submit')
        ->assertHasFormErrors(['phone']);

    expect(Delegate::withoutGlobalScopes()->count())->toBe(0);
});

it('accepts a foreign phone number in international format', function () {
    mockGoogleWorkspaceService()->shouldReceive('addToGroup')->zeroOrMoreTimes()->andReturnTrue();
    [$user, $collegeOrganization, $seat] = eligibleDelegateCandidate();

    actingAs($user);

    livewire(RequestDelegateSeat::class)
        ->fillForm([...validSeatRequestData($user, $collegeOrganization, $seat), 'phone' => '+447911123456'])
        ->call('submit')
        ->assertHasNoFormErrors();

    expect($user->refresh()->phone)->toBe('+447911123456');
});

it('creates a pending delegate, saves user fields and leaves volunteers untouched', function () {
    freezeTime();
    mockGoogleWorkspaceService()->shouldReceive('addToGroup')->zeroOrMoreTimes()->andReturnTrue();
    [$user, $collegeOrganization, $seat] = eligibleDelegateCandidate();
    $volunteersBefore = Volunteer::withoutGlobalScopes()->get(['id', 'position_title'])->toArray();

    actingAs($user);

    livewire(RequestDelegateSeat::class)
        ->fillForm(validSeatRequestData($user, $collegeOrganization, $seat))
        ->call('submit')
        ->assertHasNoFormErrors()
        ->assertNotified(__('delegate_form.notifications.submitted.title'))
        ->assertRedirect(filament()->getHomeUrl());

    $delegate = Delegate::withoutGlobalScopes()->where('user_id', $user->id)->sole();

    expect($delegate)
        ->organization_id->toBe($collegeOrganization->organization_id)
        ->seat_id->toBe($seat->id)
        ->isPending()->toBeTrue()
        ->and($user->refresh())
        ->phone->toBe('+33601020304')
        ->study_program->toBe('Master 2 Droit')
        ->address_street->toBe('1 rue de la Paix')
        ->address_complement->toBe('Bâtiment B')
        ->address_postcode->toBe('75002')
        ->address_city->toBe('Paris')
        ->elected_mandates->toBe('CFVU')
        ->and(Volunteer::withoutGlobalScopes()->get(['id', 'position_title'])->toArray())->toBe($volunteersBefore);
});

it('offers no seats for an organization the user is not eligible for', function () {
    [$user, $collegeOrganization] = eligibleDelegateCandidate();
    $foreign = CollegeOrganization::factory()->withSeats(1)->create();

    actingAs($user);

    livewire(RequestDelegateSeat::class)
        ->fillForm(['organization_id' => $foreign->organization_id])
        ->assertFormFieldExists('seat_id', fn ($field): bool => $field->getOptions() === [])
        ->fillForm(['organization_id' => $collegeOrganization->organization_id])
        ->assertFormFieldExists('seat_id', fn ($field): bool => count($field->getOptions()) === 1);
});

it('asks for confirmation before sending a request when the user already holds a seat', function () {
    mockGoogleWorkspaceService()->shouldIgnoreMissing();
    [$user, $collegeOrganization, $seat] = eligibleDelegateCandidate();
    Delegate::factory()->approved()->create(['user_id' => $user->id, 'started_at' => now()->subYear(), 'ended_at' => null]);

    actingAs($user);

    livewire(RequestDelegateSeat::class)
        ->fillForm(validSeatRequestData($user, $collegeOrganization, $seat))
        ->call('submit')
        ->assertActionMounted('confirmSubmission');

    expect(Delegate::withoutGlobalScopes()->where('user_id', $user->id)->count())->toBe(1);

    livewire(RequestDelegateSeat::class)
        ->fillForm(validSeatRequestData($user, $collegeOrganization, $seat))
        ->callAction('confirmSubmission')
        ->assertNotified(__('delegate_form.notifications.submitted.title'));

    expect(Delegate::withoutGlobalScopes()->where('user_id', $user->id)->count())->toBe(2);
});

it('asks for confirmation when the user already holds an upcoming approved seat', function () {
    mockGoogleWorkspaceService()->shouldIgnoreMissing();
    [$user, $collegeOrganization, $seat] = eligibleDelegateCandidate();
    Delegate::factory()->approved()->create(['user_id' => $user->id, 'started_at' => now()->addMonth(), 'ended_at' => null]);

    actingAs($user);

    livewire(RequestDelegateSeat::class)
        ->fillForm(validSeatRequestData($user, $collegeOrganization, $seat))
        ->call('submit')
        ->assertActionMounted('confirmSubmission');
});

it('notifies the user instead of submitting when a request became pending meanwhile', function (bool $throughConfirmation) {
    mockGoogleWorkspaceService()->shouldIgnoreMissing();
    [$user, $collegeOrganization, $seat] = eligibleDelegateCandidate();
    if ($throughConfirmation) {
        Delegate::factory()->approved()->create(['user_id' => $user->id, 'started_at' => now()->subYear(), 'ended_at' => null]);
    }

    actingAs($user);

    $component = livewire(RequestDelegateSeat::class)
        ->fillForm(validSeatRequestData($user, $collegeOrganization, $seat));

    Delegate::factory()->pending()->create(['user_id' => $user->id]);

    $throughConfirmation
        ? $component->callAction('confirmSubmission')
        : $component->call('submit');

    $component
        ->assertNotified(__('delegate_form.notifications.locked.title'))
        ->assertNoRedirect();

    expect(Delegate::withoutGlobalScopes()->where('user_id', $user->id)->pending()->count())->toBe(1);
})->with([
    'direct submit' => false,
    'confirm modal' => true,
]);

it('rejects a disposable email address', function () {
    [$user, $collegeOrganization, $seat] = eligibleDelegateCandidate();

    actingAs($user);

    livewire(RequestDelegateSeat::class)
        ->fillForm([...validSeatRequestData($user, $collegeOrganization, $seat), 'email' => 'camille@mailinator.com'])
        ->call('submit')
        ->assertHasFormErrors(['email' => 'disposable_email']);

    expect(Delegate::withoutGlobalScopes()->count())->toBe(0);
});

it('accepts the current disposable email address of a grandfathered user', function () {
    [$user, $collegeOrganization, $seat] = eligibleDelegateCandidate();
    $user->forceFill(['email' => 'camille@mailinator.com'])->save();

    actingAs($user);

    livewire(RequestDelegateSeat::class)
        ->fillForm([...validSeatRequestData($user, $collegeOrganization, $seat), 'email' => 'camille@mailinator.com'])
        ->call('submit')
        ->assertHasNoFormErrors();
});

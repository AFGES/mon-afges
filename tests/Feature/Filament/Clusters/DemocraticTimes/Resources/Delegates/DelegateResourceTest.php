<?php

declare(strict_types=1);

use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Pages\CreateDelegate;
use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Pages\EditDelegate;
use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Pages\ListDelegates;
use App\Models\CollegeOrganization;
use App\Models\Delegate;
use App\Models\Organization;
use App\Models\Seat;
use App\Models\User;
use App\Notifications\DelegateApprovalRequestedNotification;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Livewire\livewire;

function actingAsSuperAdminForDelegateResource(): User
{
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole('super_admin');

    actingAs($user);

    return $user;
}

/**
 * @return Collection<int, Delegate>
 */
function createDelegatesWithDistinctSearchableValues(): Collection
{
    return Delegate::factory()
        ->count(3)
        ->approved()
        ->sequence(
            [
                'email' => 'first@alpha.test',
                'user_id' => User::factory()->state(['first_name' => 'Aurelia', 'last_name' => 'Voss']),
                'organization_id' => Organization::factory()->state(['short_name' => 'Zephyr']),
            ],
            [
                'email' => 'second@bravo.test',
                'user_id' => User::factory()->state(['first_name' => 'Bastien', 'last_name' => 'Corbel']),
                'organization_id' => Organization::factory()->state(['short_name' => 'Quasar']),
            ],
            [
                'email' => 'third@charlie.test',
                'user_id' => User::factory()->state(['first_name' => 'Cyrille', 'last_name' => 'Danton']),
                'organization_id' => Organization::factory()->state(['short_name' => 'Nimbus']),
            ],
        )
        ->create();
}

describe('ListDelegates', function () {
    it('renders the page', function () {
        actingAsSuperAdminForDelegateResource();

        livewire(ListDelegates::class)
            ->assertOk();
    });

    it('lists the existing delegates', function () {
        actingAsSuperAdminForDelegateResource();

        $delegates = Delegate::factory()->count(5)->approved()->create();

        livewire(ListDelegates::class)
            ->assertCanSeeTableRecords($delegates);
    });

    it('lists pending delegates although they are not approved', function () {
        actingAsSuperAdminForDelegateResource();

        $pendingDelegate = Delegate::factory()->pending()->create();
        $approvedDelegate = Delegate::factory()->approved()->create();

        livewire(ListDelegates::class)
            ->assertCanSeeTableRecords([$pendingDelegate])
            ->assertCanNotSeeTableRecords([$approvedDelegate])
            ->filterTable('approval_status', ['approved'])
            ->assertCanSeeTableRecords([$approvedDelegate])
            ->assertCanNotSeeTableRecords([$pendingDelegate]);
    });

    it('lists only delegates matching the searched user name', function () {
        actingAsSuperAdminForDelegateResource();

        $delegates = createDelegatesWithDistinctSearchableValues();

        livewire(ListDelegates::class)
            ->searchTable($delegates->first()->user->name)
            ->assertCanSeeTableRecords($delegates->take(1))
            ->assertCanNotSeeTableRecords($delegates->skip(1));
    });

    it('lists only delegates matching the searched email', function () {
        actingAsSuperAdminForDelegateResource();

        $delegates = createDelegatesWithDistinctSearchableValues();

        livewire(ListDelegates::class)
            ->searchTable($delegates->first()->email)
            ->assertCanSeeTableRecords($delegates->take(1))
            ->assertCanNotSeeTableRecords($delegates->skip(1));
    });

    it('lists only delegates matching the searched organization', function () {
        actingAsSuperAdminForDelegateResource();

        $delegates = createDelegatesWithDistinctSearchableValues();

        livewire(ListDelegates::class)
            ->searchTable($delegates->first()->organization->short_name)
            ->assertCanSeeTableRecords($delegates->take(1))
            ->assertCanNotSeeTableRecords($delegates->skip(1));
    });

    it('sorts delegates by user name', function () {
        actingAsSuperAdminForDelegateResource();

        $organization = Organization::factory()->create();
        $bravo = Delegate::factory()->approved()->for($organization)->for(User::factory()->state(['first_name' => 'Test', 'last_name' => 'Bravo']))->create(['ended_at' => null]);
        $charlie = Delegate::factory()->approved()->for($organization)->for(User::factory()->state(['first_name' => 'Test', 'last_name' => 'Charlie']))->create(['ended_at' => null]);
        $alpha = Delegate::factory()->approved()->for($organization)->for(User::factory()->state(['first_name' => 'Test', 'last_name' => 'Alpha']))->create(['ended_at' => null]);

        livewire(ListDelegates::class)
            ->sortTable('user.name')
            ->assertCanSeeTableRecords([$alpha, $bravo, $charlie], inOrder: true)
            ->sortTable('user.name', 'desc')
            ->assertCanSeeTableRecords([$charlie, $bravo, $alpha], inOrder: true);
    });

    it('sorts delegates by email', function () {
        actingAsSuperAdminForDelegateResource();

        $organization = Organization::factory()->create();
        $bravo = Delegate::factory()->approved()->for($organization)->create(['email' => 'bravo@example.org', 'ended_at' => null]);
        $charlie = Delegate::factory()->approved()->for($organization)->create(['email' => 'charlie@example.org', 'ended_at' => null]);
        $alpha = Delegate::factory()->approved()->for($organization)->create(['email' => 'alpha@example.org', 'ended_at' => null]);

        livewire(ListDelegates::class)
            ->sortTable('email')
            ->assertCanSeeTableRecords([$alpha, $bravo, $charlie], inOrder: true)
            ->sortTable('email', 'desc')
            ->assertCanSeeTableRecords([$charlie, $bravo, $alpha], inOrder: true);
    });

    it('sorts delegates by started_at', function () {
        actingAsSuperAdminForDelegateResource();

        $this->travelTo('2026-06-15 10:00:00');
        $organization = Organization::factory()->create();
        $middle = Delegate::factory()->approved()->for($organization)->create(['started_at' => '2026-03-01', 'ended_at' => null]);
        $latest = Delegate::factory()->approved()->for($organization)->create(['started_at' => '2026-05-01', 'ended_at' => null]);
        $earliest = Delegate::factory()->approved()->for($organization)->create(['started_at' => '2026-01-01', 'ended_at' => null]);

        livewire(ListDelegates::class)
            ->sortTable('started_at')
            ->assertCanSeeTableRecords([$earliest, $middle, $latest], inOrder: true)
            ->sortTable('started_at', 'desc')
            ->assertCanSeeTableRecords([$latest, $middle, $earliest], inOrder: true);
    });

    it('orders organization groups by legal name and leaves the grouped organization column unsortable', function () {
        actingAsSuperAdminForDelegateResource();

        $zuluLegalName = Organization::factory()->create(['short_name' => 'Alpha', 'legal_name' => 'Zulu legal']);
        $yankeeLegalName = Organization::factory()->create(['short_name' => 'Bravo', 'legal_name' => 'Yankee legal']);
        $first = Delegate::factory()->approved()->for($zuluLegalName)->create(['ended_at' => null]);
        $second = Delegate::factory()->approved()->for($yankeeLegalName)->create(['ended_at' => null]);

        livewire(ListDelegates::class)
            ->assertTableColumnExists('organization.short_name', fn ($column) => ! $column->isSortable())
            ->assertCanSeeTableRecords([$second, $first], inOrder: true);
    });
});

describe('CreateDelegate', function () {
    it('renders the page', function () {
        actingAsSuperAdminForDelegateResource();

        livewire(CreateDelegate::class)
            ->assertOk();
    });

    it('creates a delegate from valid form data', function () {
        actingAsSuperAdminForDelegateResource();

        $user = User::factory()->create();
        $seat = Seat::factory()->create();
        $organization = $seat->collegeOrganization->organization;

        livewire(CreateDelegate::class)
            ->fillForm([
                'started_at' => today()->format('Y-m-d'),
                'ended_at' => today()->addYear()->format('Y-m-d'),
                'user_id' => $user->id,
                'email' => fake()->email(),
                'organization_id' => $organization->id,
                'seat_id' => $seat->id,
            ])
            ->call('create')
            ->assertNotified()
            ->assertRedirect();

        assertDatabaseHas(Delegate::class, [
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'seat_id' => $seat->id,
        ]);
    });

    it('creates the delegate already approved without asking for approval', function () {
        $admin = actingAsSuperAdminForDelegateResource();
        mockGoogleWorkspaceService()->shouldIgnoreMissing();
        Permission::firstOrCreate(['name' => 'Update:Delegate', 'guard_name' => 'web']);
        User::factory()->create()->givePermissionTo('Update:Delegate');
        Notification::fake();

        $seat = Seat::factory()->create();

        livewire(CreateDelegate::class)
            ->fillForm([
                'started_at' => today()->format('Y-m-d'),
                'user_id' => User::factory()->create()->id,
                'email' => fake()->email(),
                'organization_id' => $seat->collegeOrganization->organization_id,
                'seat_id' => $seat->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $delegate = Delegate::withoutGlobalScopes()->where('seat_id', $seat->id)->sole();

        expect($delegate->isApproved())->toBeTrue()
            ->and($delegate->approved_by)->toBe($admin->id)
            ->and($delegate->approved_at)->not->toBeNull();
        Notification::assertNotSentTo(User::permission('Update:Delegate')->get(), DelegateApprovalRequestedNotification::class);
    });

    it('refuses a delegate on a full seat when no current delegate is ended', function () {
        actingAsSuperAdminForDelegateResource();
        mockGoogleWorkspaceService()->shouldIgnoreMissing();

        $seat = Seat::factory()->create([
            'college_organization_id' => CollegeOrganization::factory()->create(['delegates_per_seat' => 1])->id,
        ]);
        Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->subYear(), 'ended_at' => null]);

        livewire(CreateDelegate::class)
            ->fillForm([
                'started_at' => today()->format('Y-m-d'),
                'user_id' => User::factory()->create()->id,
                'email' => 'full-seat@example.org',
                'organization_id' => $seat->collegeOrganization->organization_id,
                'seat_id' => $seat->id,
                'terminated_delegate_ids' => [],
            ])
            ->call('create')
            ->assertHasFormErrors(['terminated_delegate_ids']);

        assertDatabaseMissing(Delegate::class, ['email' => 'full-seat@example.org']);
    });

    it('ends the selected current delegate when creating on a full seat', function () {
        actingAsSuperAdminForDelegateResource();
        mockGoogleWorkspaceService()->shouldIgnoreMissing();

        $seat = Seat::factory()->create([
            'college_organization_id' => CollegeOrganization::factory()->create(['delegates_per_seat' => 1])->id,
        ]);
        $current = Delegate::factory()->approved()->create(['seat_id' => $seat->id, 'started_at' => now()->subYear(), 'ended_at' => null]);
        $startedAt = today()->subDays(3);

        livewire(CreateDelegate::class)
            ->fillForm([
                'started_at' => $startedAt->format('Y-m-d'),
                'user_id' => User::factory()->create()->id,
                'email' => 'replacement@example.org',
                'organization_id' => $seat->collegeOrganization->organization_id,
                'seat_id' => $seat->id,
                'terminated_delegate_ids' => [$current->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        expect(Delegate::withoutGlobalScopes()->find($current->id)->ended_at->toDateString())->toBe($startedAt->toDateString())
            ->and(Delegate::withoutGlobalScopes()->where('email', 'replacement@example.org')->sole()->isApproved())->toBeTrue();
    });

    it('creates a delegate from the minimal valid form data', function () {
        actingAsSuperAdminForDelegateResource();

        $user = User::factory()->create();
        $seat = Seat::factory()->create();
        $organization = $seat->collegeOrganization->organization;

        livewire(CreateDelegate::class)
            ->fillForm([
                'started_at' => today()->format('Y-m-d'),
                'user_id' => $user->id,
                'email' => fake()->email(),
                'organization_id' => $organization->id,
                'seat_id' => $seat->id,
            ])
            ->call('create')
            ->assertNotified()
            ->assertRedirect();

        assertDatabaseHas(Delegate::class, [
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'seat_id' => $seat->id,
        ]);
    });

    it('rejects the form when a required field is missing', function (array $data, array $errors) {
        actingAsSuperAdminForDelegateResource();

        $user = User::factory()->create();
        $seat = Seat::factory()->create();
        $organization = $seat->collegeOrganization->organization;

        livewire(CreateDelegate::class)
            ->fillForm([
                'started_at' => today()->format('Y-m-d'),
                'user_id' => $user->id,
                'email' => fake()->email(),
                'organization_id' => $organization->id,
                'seat_id' => $seat->id,
                ...$data,
            ])
            ->call('create')
            ->assertHasFormErrors($errors)
            ->assertNotNotified()
            ->assertNoRedirect();
    })->with([
        '`started_at` is required' => [['started_at' => null], ['started_at' => 'required']],
        '`user_id` is required' => [['user_id' => null], ['user_id' => 'required']],
        '`email` is required' => [['email' => null], ['email' => 'required']],
        '`organization_id` is required' => [['organization_id' => null], ['organization_id' => 'required']],
        '`seat_id` is required' => [['seat_id' => null], ['seat_id' => 'required']],
    ]);

    it('rejects an email that is not a valid address', function () {
        actingAsSuperAdminForDelegateResource();

        $user = User::factory()->create();
        $seat = Seat::factory()->create();
        $organization = $seat->collegeOrganization->organization;

        livewire(CreateDelegate::class)
            ->fillForm([
                'started_at' => today()->format('Y-m-d'),
                'user_id' => $user->id,
                'email' => 'invalid-email',
                'organization_id' => $organization->id,
                'seat_id' => $seat->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => 'email'])
            ->assertNotNotified()
            ->assertNoRedirect();
    });

    it('rejects an end date before the start date', function () {
        actingAsSuperAdminForDelegateResource();

        $user = User::factory()->create();
        $seat = Seat::factory()->create();
        $organization = $seat->collegeOrganization->organization;

        livewire(CreateDelegate::class)
            ->fillForm([
                'started_at' => today()->format('Y-m-d'),
                'ended_at' => today()->subDay()->format('Y-m-d'),
                'user_id' => $user->id,
                'email' => fake()->email(),
                'organization_id' => $organization->id,
                'seat_id' => $seat->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['ended_at'])
            ->assertNotNotified()
            ->assertNoRedirect();
    });

    it('shows the translated required message for email', function () {
        actingAsSuperAdminForDelegateResource();

        livewire(CreateDelegate::class)
            ->fillForm(['started_at' => '2026-01-01', 'user_id' => User::factory()->create()->id, 'organization_id' => Organization::factory()->create()->id, 'seat_id' => Seat::factory()->create()->id, 'email' => null])
            ->call('create')
            ->assertHasFormErrors(['email' => [
                __('validation.required', ['attribute' => Str::lower(__('delegate.fields.email'))]),
            ]]);
    });
});

describe('EditDelegate', function () {
    it('renders the page', function () {
        actingAsSuperAdminForDelegateResource();

        $delegate = Delegate::factory()->approved()->create();

        livewire(EditDelegate::class, [
            'record' => $delegate->id,
        ])
            ->assertOk()
            ->assertSchemaStateSet([
                'started_at' => $delegate->started_at->format('Y-m-d'),
                'user_id' => $delegate->user_id,
                'email' => $delegate->email,
                'organization_id' => $delegate->organization_id,
                'seat_id' => $delegate->seat_id,
            ]);
    });

    it('updates a delegate from valid form data', function () {
        actingAsSuperAdminForDelegateResource();

        $delegate = Delegate::factory()->approved()->create();
        $newUser = User::factory()->create();
        $newEmail = fake()->email();

        livewire(EditDelegate::class, [
            'record' => $delegate->id,
        ])
            ->fillForm([
                'user_id' => $newUser->id,
                'email' => $newEmail,
            ])
            ->call('save')
            ->assertNotified();

        assertDatabaseHas(Delegate::class, [
            'id' => $delegate->id,
            'user_id' => $newUser->id,
            'email' => $newEmail,
        ]);
    });

    it('saves the user fiche fields through the user relationship', function () {
        actingAsSuperAdminForDelegateResource();

        $delegate = Delegate::factory()->approved()->create();

        livewire(EditDelegate::class, [
            'record' => $delegate->id,
        ])
            ->fillForm([
                'user' => [
                    'last_name' => 'Durand',
                    'first_name' => 'Camille',
                    'study_program' => 'Master 2 Droit',
                    'phone' => '0601020304',
                    'postal_address' => '1 rue de la Paix, Paris',
                    'elected_mandates' => 'CFVU',
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas(User::class, [
            'id' => $delegate->user_id,
            'last_name' => 'Durand',
            'first_name' => 'Camille',
            'study_program' => 'Master 2 Droit',
            'phone' => '0601020304',
            'elected_mandates' => 'CFVU',
        ]);
    });

    it('saves the edit form with empty optional user fields', function () {
        actingAsSuperAdminForDelegateResource();

        $delegate = Delegate::factory()->approved()->create();

        livewire(EditDelegate::class, [
            'record' => $delegate->id,
        ])
            ->fillForm([
                'user' => [
                    'phone' => '',
                    'birth_date' => null,
                    'study_program' => '',
                    'postal_address' => '',
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();
    });

    it('updates the delegate dates from valid form data', function () {
        actingAsSuperAdminForDelegateResource();

        $delegate = Delegate::factory()->approved()->create();
        $newStartedAt = today()->addMonth();
        $newEndedAt = today()->addMonths(3);

        livewire(EditDelegate::class, [
            'record' => $delegate->id,
        ])
            ->fillForm([
                'started_at' => $newStartedAt->format('Y-m-d'),
                'ended_at' => $newEndedAt->format('Y-m-d'),
            ])
            ->call('save')
            ->assertNotified();

        $delegate->refresh();

        expect($delegate->started_at->format('Y-m-d'))->toBe($newStartedAt->format('Y-m-d'));
        expect($delegate->ended_at->format('Y-m-d'))->toBe($newEndedAt->format('Y-m-d'));
    });

    it('updates the delegate organization and seat from valid form data', function () {
        actingAsSuperAdminForDelegateResource();

        $delegate = Delegate::factory()->approved()->create();
        $newSeat = Seat::factory()->create();
        $newOrganization = $newSeat->collegeOrganization->organization;

        livewire(EditDelegate::class, [
            'record' => $delegate->id,
        ])
            ->fillForm([
                'organization_id' => $newOrganization->id,
                'seat_id' => $newSeat->id,
            ])
            ->call('save')
            ->assertNotified();

        assertDatabaseHas(Delegate::class, [
            'id' => $delegate->id,
            'organization_id' => $newOrganization->id,
            'seat_id' => $newSeat->id,
        ]);
    });

    it('rejects invalid form data on edit', function (array $data, array $errors) {
        actingAsSuperAdminForDelegateResource();

        $delegate = Delegate::factory()->approved()->create();

        livewire(EditDelegate::class, [
            'record' => $delegate->id,
        ])
            ->fillForm($data)
            ->call('save')
            ->assertHasFormErrors($errors)
            ->assertNotNotified();
    })->with([
        '`started_at` is required' => [['started_at' => null], ['started_at' => 'required']],
        '`user_id` is required' => [['user_id' => null], ['user_id' => 'required']],
        '`email` is required' => [['email' => null], ['email' => 'required']],
        '`email` is a valid email' => [['email' => 'invalid-email'], ['email' => 'email']],
        '`organization_id` is required' => [['organization_id' => null], ['organization_id' => 'required']],
        '`seat_id` is required' => [['seat_id' => null], ['seat_id' => 'required']],
    ]);

    it('soft deletes the delegate when the delete action is called', function () {
        actingAsSuperAdminForDelegateResource();

        $delegate = Delegate::factory()->approved()->create();

        livewire(EditDelegate::class, [
            'record' => $delegate->id,
        ])
            ->callAction(DeleteAction::class)
            ->assertNotified()
            ->assertRedirect();

        assertSoftDeleted(Delegate::class, [
            'id' => $delegate->id,
        ]);
    });

    it('restores the delegate when the restore action is called on a soft deleted record', function () {
        actingAsSuperAdminForDelegateResource();

        $delegate = Delegate::factory()->approved()->create();
        $delegate->delete();

        livewire(EditDelegate::class, [
            'record' => $delegate->id,
        ])
            ->callAction(RestoreAction::class)
            ->assertNotified();

        assertDatabaseHas(Delegate::class, [
            'id' => $delegate->id,
            'deleted_at' => null,
        ]);
    });

    it('permanently deletes the delegate when the force delete action is called', function () {
        actingAsSuperAdminForDelegateResource();

        $delegate = Delegate::factory()->approved()->create();
        $delegate->delete();

        livewire(EditDelegate::class, [
            'record' => $delegate->id,
        ])
            ->callAction(ForceDeleteAction::class)
            ->assertNotified()
            ->assertRedirect();

        assertDatabaseMissing(Delegate::class, [
            'id' => $delegate->id,
        ]);
    });
});

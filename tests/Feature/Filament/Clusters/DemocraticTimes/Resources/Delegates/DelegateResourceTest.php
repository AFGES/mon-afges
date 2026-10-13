<?php

declare(strict_types=1);

use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Pages\CreateDelegate;
use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Pages\EditDelegate;
use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Pages\ListDelegates;
use App\Models\Delegate;
use App\Models\Organization;
use App\Models\Seat;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
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

describe('ListDelegates', function () {
    it('can load the page', function () {
        actingAsSuperAdminForDelegateResource();

        livewire(ListDelegates::class)
            ->assertOk();
    });

    it('can list delegates', function () {
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

    it('can search delegates by user name', function () {
        actingAsSuperAdminForDelegateResource();

        $delegates = Delegate::factory()->count(5)->approved()->create();

        livewire(ListDelegates::class)
            ->searchTable($delegates->first()->user->name)
            ->assertCanSeeTableRecords($delegates->take(1))
            ->assertCanNotSeeTableRecords($delegates->skip(1));
    });

    it('can search delegates by email', function () {
        actingAsSuperAdminForDelegateResource();

        $delegates = Delegate::factory()->count(5)->approved()->create();

        livewire(ListDelegates::class)
            ->searchTable($delegates->first()->email)
            ->assertCanSeeTableRecords($delegates->take(1))
            ->assertCanNotSeeTableRecords($delegates->skip(1));
    });

    it('can search delegates by organization', function () {
        actingAsSuperAdminForDelegateResource();

        $delegates = Delegate::factory()->count(5)->approved()->create();

        livewire(ListDelegates::class)
            ->searchTable($delegates->first()->organization->short_name)
            ->assertCanSeeTableRecords($delegates->take(1))
            ->assertCanNotSeeTableRecords($delegates->skip(1));
    });

    it('sorts delegates by user name', function () {
        actingAsSuperAdminForDelegateResource();

        $organization = Organization::factory()->create();
        $bravo = Delegate::factory()->approved()->for($organization)->for(User::factory()->state(['name' => 'Bravo']))->create(['ended_at' => null]);
        $charlie = Delegate::factory()->approved()->for($organization)->for(User::factory()->state(['name' => 'Charlie']))->create(['ended_at' => null]);
        $alpha = Delegate::factory()->approved()->for($organization)->for(User::factory()->state(['name' => 'Alpha']))->create(['ended_at' => null]);

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

    it('sorts delegates by organization name ascending within the organization grouping', function () {
        actingAsSuperAdminForDelegateResource();

        $bravoOrganization = Organization::factory()->create(['short_name' => 'Bravo', 'legal_name' => 'Bravo legal']);
        $alphaOrganization = Organization::factory()->create(['short_name' => 'Alpha', 'legal_name' => 'Alpha legal']);
        $bravo = Delegate::factory()->approved()->for($bravoOrganization)->create(['ended_at' => null]);
        $alpha = Delegate::factory()->approved()->for($alphaOrganization)->create(['ended_at' => null]);

        livewire(ListDelegates::class)
            ->sortTable('organization.short_name')
            ->assertCanSeeTableRecords([$alpha, $bravo], inOrder: true);
    });
});

describe('CreateDelegate', function () {
    it('can load the page', function () {
        actingAsSuperAdminForDelegateResource();

        livewire(CreateDelegate::class)
            ->assertOk();
    });

    it('can create a delegate', function () {
        actingAsSuperAdminForDelegateResource();

        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $seat = Seat::factory()->create();

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

    it('can create a delegate with minimal data', function () {
        actingAsSuperAdminForDelegateResource();

        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $seat = Seat::factory()->create();

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

    it('validates required fields', function (array $data, array $errors) {
        actingAsSuperAdminForDelegateResource();

        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $seat = Seat::factory()->create();

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

    it('validates email format', function () {
        actingAsSuperAdminForDelegateResource();

        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $seat = Seat::factory()->create();

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

    it('validates ended_at is after started_at', function () {
        actingAsSuperAdminForDelegateResource();

        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $seat = Seat::factory()->create();

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
});

describe('EditDelegate', function () {
    it('can load the page', function () {
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

    it('can update a delegate', function () {
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

    it('can update delegate dates', function () {
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

    it('can update delegate organization and seat', function () {
        actingAsSuperAdminForDelegateResource();

        $delegate = Delegate::factory()->approved()->create();
        $newOrganization = Organization::factory()->create();
        $newSeat = Seat::factory()->create();

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

    it('validates the form data', function (array $data, array $errors) {
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

    it('can soft delete a delegate', function () {
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

    it('can restore a soft deleted delegate', function () {
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

    it('can force delete a delegate', function () {
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

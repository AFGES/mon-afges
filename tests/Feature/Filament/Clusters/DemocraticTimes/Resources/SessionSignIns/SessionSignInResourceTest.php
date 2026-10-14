<?php

declare(strict_types=1);

use App\Filament\Clusters\DemocraticTimes\Resources\SessionSignIns\Pages\CreateSessionSignIn;
use App\Filament\Clusters\DemocraticTimes\Resources\SessionSignIns\Pages\EditSessionSignIn;
use App\Filament\Clusters\DemocraticTimes\Resources\SessionSignIns\Pages\ListSessionSignIns;
use App\Models\Delegate;
use App\Models\DemocraticTimeSession;
use App\Models\Seat;
use App\Models\SessionSignIn;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertModelMissing;
use function Pest\Livewire\livewire;

function actingAsSuperAdminForSessionSignInResource(): User
{
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole('super_admin');

    actingAs($user);

    return $user;
}

describe('ListSessionSignIns', function () {
    it('can load the page', function () {
        actingAsSuperAdminForSessionSignInResource();

        livewire(ListSessionSignIns::class)
            ->assertOk();
    });

    it('can list session sign-ins', function () {
        actingAsSuperAdminForSessionSignInResource();

        $sessionSignIns = SessionSignIn::factory()->count(3)->create();

        livewire(ListSessionSignIns::class)
            ->assertCanSeeTableRecords($sessionSignIns);
    });

    it('can search session sign-ins by session location', function () {
        actingAsSuperAdminForSessionSignInResource();

        $signIn1 = SessionSignIn::factory()->create([
            'democratic_time_session_id' => DemocraticTimeSession::factory()->create(['location' => 'Main Hall']),
            'delegate_id' => Delegate::factory()->approved()->create(['email' => 'first@alpha.test']),
        ]);

        $signIn2 = SessionSignIn::factory()->create([
            'democratic_time_session_id' => DemocraticTimeSession::factory()->create(['location' => 'Conference Room']),
            'delegate_id' => Delegate::factory()->approved()->create(['email' => 'second@bravo.test']),
        ]);

        livewire(ListSessionSignIns::class)
            ->searchTable('Main Hall')
            ->assertCanSeeTableRecords([$signIn1])
            ->assertCanNotSeeTableRecords([$signIn2]);
    });

    it('can search session sign-ins by delegate email', function () {
        actingAsSuperAdminForSessionSignInResource();

        $signIn1 = SessionSignIn::factory()->create([
            'democratic_time_session_id' => DemocraticTimeSession::factory()->create(['location' => 'Main Hall']),
            'delegate_id' => Delegate::factory()->approved()->create(['email' => 'john@alpha.test']),
        ]);

        $signIn2 = SessionSignIn::factory()->create([
            'democratic_time_session_id' => DemocraticTimeSession::factory()->create(['location' => 'Conference Room']),
            'delegate_id' => Delegate::factory()->approved()->create(['email' => 'jane@bravo.test']),
        ]);

        livewire(ListSessionSignIns::class)
            ->searchTable('john@alpha.test')
            ->assertCanSeeTableRecords([$signIn1])
            ->assertCanNotSeeTableRecords([$signIn2]);
    });

    it('can filter session sign-ins by session', function () {
        actingAsSuperAdminForSessionSignInResource();

        $session1 = DemocraticTimeSession::factory()->create();
        $session2 = DemocraticTimeSession::factory()->create();

        $signIn1 = SessionSignIn::factory()->create([
            'democratic_time_session_id' => $session1->id,
        ]);

        $signIn2 = SessionSignIn::factory()->create([
            'democratic_time_session_id' => $session2->id,
        ]);

        livewire(ListSessionSignIns::class)
            ->filterTable('democratic_time_session_id', $session1->id)
            ->assertCanSeeTableRecords([$signIn1])
            ->assertCanNotSeeTableRecords([$signIn2]);
    });

    it('can filter session sign-ins by signed in status', function (string $filterValue, string $visibleSignIn, string $hiddenSignIn) {
        actingAsSuperAdminForSessionSignInResource();
        $this->travelTo('2026-01-15 12:00:00');

        $signIns = [
            'signed in' => SessionSignIn::factory()->create(['signed_out_at' => null]),
            'signed out' => SessionSignIn::factory()->create([
                'signed_in_at' => now()->subHours(2),
                'signed_out_at' => now()->subHour(),
            ]),
        ];

        livewire(ListSessionSignIns::class)
            ->filterTable('is_signed_in', $filterValue)
            ->assertCanSeeTableRecords([$signIns[$visibleSignIn]])
            ->assertCanNotSeeTableRecords([$signIns[$hiddenSignIn]]);
    })->with([
        'yes keeps signed in records' => ['yes', 'signed in', 'signed out'],
        'no keeps signed out records' => ['no', 'signed out', 'signed in'],
    ]);
});

describe('CreateSessionSignIn', function () {
    it('can load the page', function () {
        actingAsSuperAdminForSessionSignInResource();

        livewire(CreateSessionSignIn::class)
            ->assertOk();
    });

    it('can create a session sign-in', function () {
        actingAsSuperAdminForSessionSignInResource();
        $this->travelTo('2026-01-15 12:00:00');

        $session = DemocraticTimeSession::factory()->create();
        $seat = Seat::factory()->create();
        $delegate = Delegate::factory()->approved()->for($seat)->create();
        $signedInAt = now()->subMinutes(10);

        livewire(CreateSessionSignIn::class)
            ->fillForm([
                'democratic_time_session_id' => $session->id,
                'seat_id' => $seat->id,
                'delegate_id' => $delegate->id,
                'signed_in_at' => $signedInAt->format('Y-m-d H:i:s'),
            ])
            ->call('create')
            ->assertNotified()
            ->assertRedirect();

        assertDatabaseHas(SessionSignIn::class, [
            'democratic_time_session_id' => $session->id,
            'seat_id' => $seat->id,
            'delegate_id' => $delegate->id,
            'signed_in_at' => $signedInAt->toDateTimeString(),
        ]);
    });

    it('can create a session sign-in with proxy', function () {
        actingAsSuperAdminForSessionSignInResource();
        $this->travelTo('2026-01-15 12:00:00');

        $session = DemocraticTimeSession::factory()->create();
        $seat = Seat::factory()->create();
        $delegate = Delegate::factory()->approved()->for($seat)->create();
        $proxySeat = Seat::factory()->create();
        $signedInAt = now()->subMinutes(10);

        livewire(CreateSessionSignIn::class)
            ->fillForm([
                'democratic_time_session_id' => $session->id,
                'seat_id' => $seat->id,
                'delegate_id' => $delegate->id,
                'signed_in_by_seat_id' => $proxySeat->id,
                'signed_in_at' => $signedInAt->format('Y-m-d H:i:s'),
            ])
            ->call('create')
            ->assertNotified()
            ->assertRedirect();

        assertDatabaseHas(SessionSignIn::class, [
            'democratic_time_session_id' => $session->id,
            'seat_id' => $seat->id,
            'delegate_id' => $delegate->id,
            'signed_in_by_seat_id' => $proxySeat->id,
        ]);
    });

    it('can create a session sign-in with sign out time', function () {
        actingAsSuperAdminForSessionSignInResource();
        $this->travelTo('2026-01-15 12:00:00');

        $session = DemocraticTimeSession::factory()->create();
        $seat = Seat::factory()->create();
        $delegate = Delegate::factory()->approved()->for($seat)->create();
        $signedInAt = now()->subHours(2);
        $signedOutAt = now()->subMinutes(10);

        livewire(CreateSessionSignIn::class)
            ->fillForm([
                'democratic_time_session_id' => $session->id,
                'seat_id' => $seat->id,
                'delegate_id' => $delegate->id,
                'signed_in_at' => $signedInAt->format('Y-m-d H:i:s'),
                'signed_out_at' => $signedOutAt->format('Y-m-d H:i:s'),
            ])
            ->call('create')
            ->assertNotified()
            ->assertRedirect();

        assertDatabaseHas(SessionSignIn::class, [
            'democratic_time_session_id' => $session->id,
            'seat_id' => $seat->id,
            'delegate_id' => $delegate->id,
        ]);
    });

    it('validates the form data', function (array $data, array $errors) {
        actingAsSuperAdminForSessionSignInResource();

        $session = DemocraticTimeSession::factory()->create();
        $seat = Seat::factory()->create();
        $delegate = Delegate::factory()->approved()->for($seat)->create();

        livewire(CreateSessionSignIn::class)
            ->fillForm([
                'democratic_time_session_id' => $session->id,
                'seat_id' => $seat->id,
                'delegate_id' => $delegate->id,
                'signed_in_at' => '2026-01-01 10:00:00',
                ...$data,
            ])
            ->call('create')
            ->assertHasFormErrors($errors)
            ->assertNotNotified()
            ->assertNoRedirect();
    })->with([
        '`democratic_time_session_id` is required' => [
            ['democratic_time_session_id' => null],
            ['democratic_time_session_id' => 'required'],
        ],
        '`seat_id` is required' => [
            ['seat_id' => null],
            ['seat_id' => 'required'],
        ],
        '`delegate_id` is required' => [
            ['delegate_id' => null],
            ['delegate_id' => 'required'],
        ],
        '`signed_in_at` is required' => [
            ['signed_in_at' => null],
            ['signed_in_at' => 'required'],
        ],
        '`signed_out_at` must be after `signed_in_at`' => [
            ['signed_in_at' => '2026-01-01 10:00:00', 'signed_out_at' => '2026-01-01 09:00:00'],
            ['signed_out_at' => 'after'],
        ],
    ]);
});

describe('EditSessionSignIn', function () {
    it('can load the page', function () {
        actingAsSuperAdminForSessionSignInResource();

        $sessionSignIn = SessionSignIn::factory()->create();

        livewire(EditSessionSignIn::class, [
            'record' => $sessionSignIn->id,
        ])
            ->assertOk()
            ->assertSchemaStateSet([
                'democratic_time_session_id' => $sessionSignIn->democratic_time_session_id,
                'seat_id' => $sessionSignIn->seat_id,
                'delegate_id' => $sessionSignIn->delegate_id,
            ]);
    });

    it('can update a session sign-in', function () {
        actingAsSuperAdminForSessionSignInResource();
        $this->travelTo('2026-01-15 12:00:00');

        $sessionSignIn = SessionSignIn::factory()->create([
            'signed_in_at' => now()->subHours(2),
        ]);
        $newSignedOutAt = now();

        livewire(EditSessionSignIn::class, [
            'record' => $sessionSignIn->id,
        ])
            ->fillForm([
                'signed_out_at' => $newSignedOutAt->format('Y-m-d H:i:s'),
            ])
            ->call('save')
            ->assertNotified();

        expect($sessionSignIn->fresh()->signed_out_at)->toEqual($newSignedOutAt);
    });

    it('can update session sign-in to add proxy seat', function () {
        actingAsSuperAdminForSessionSignInResource();

        $sessionSignIn = SessionSignIn::factory()->create([
            'signed_in_by_seat_id' => null,
        ]);

        $proxySeat = Seat::factory()->create();

        livewire(EditSessionSignIn::class, [
            'record' => $sessionSignIn->id,
        ])
            ->fillForm([
                'signed_in_by_seat_id' => $proxySeat->id,
            ])
            ->call('save')
            ->assertNotified();

        assertDatabaseHas(SessionSignIn::class, [
            'id' => $sessionSignIn->id,
            'signed_in_by_seat_id' => $proxySeat->id,
        ]);
    });

    it('can update session sign-in to remove sign out time', function () {
        actingAsSuperAdminForSessionSignInResource();
        $this->travelTo('2026-01-15 12:00:00');

        $sessionSignIn = SessionSignIn::factory()->create([
            'signed_in_at' => now()->subHours(2),
            'signed_out_at' => now()->subHour(),
        ]);

        livewire(EditSessionSignIn::class, [
            'record' => $sessionSignIn->id,
        ])
            ->fillForm([
                'signed_out_at' => null,
            ])
            ->call('save')
            ->assertNotified();

        assertDatabaseHas(SessionSignIn::class, [
            'id' => $sessionSignIn->id,
            'signed_out_at' => null,
        ]);
    });

    it('validates that signed_out_at is after signed_in_at', function () {
        actingAsSuperAdminForSessionSignInResource();
        $this->travelTo('2026-01-15 12:00:00');

        $sessionSignIn = SessionSignIn::factory()->create([
            'signed_in_at' => now(),
        ]);

        livewire(EditSessionSignIn::class, [
            'record' => $sessionSignIn->id,
        ])
            ->fillForm([
                'signed_out_at' => now()->subHour()->format('Y-m-d H:i:s'),
            ])
            ->call('save')
            ->assertHasFormErrors(['signed_out_at' => [
                __('validation.after', [
                    'attribute' => Str::lower(__('emargement.fields.signed_out_at')),
                    'date' => Str::lower(__('emargement.fields.signed_in_at')),
                ]),
            ]])
            ->assertNotNotified();
    });

    it('can delete a session sign-in', function () {
        actingAsSuperAdminForSessionSignInResource();

        $sessionSignIn = SessionSignIn::factory()->create();

        livewire(EditSessionSignIn::class, [
            'record' => $sessionSignIn->id,
        ])
            ->callAction('delete')
            ->assertNotified();

        assertModelMissing($sessionSignIn);
    });
});

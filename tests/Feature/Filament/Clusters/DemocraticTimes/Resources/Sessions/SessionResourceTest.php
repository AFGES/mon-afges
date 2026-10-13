<?php

declare(strict_types=1);

use App\Filament\Clusters\DemocraticTimes\Resources\Sessions\Pages\CreateSession;
use App\Filament\Clusters\DemocraticTimes\Resources\Sessions\Pages\EditSession;
use App\Filament\Clusters\DemocraticTimes\Resources\Sessions\Pages\ListSessions;
use App\Models\DemocraticTimeSession;
use App\Models\DemocraticTimeType;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertModelMissing;

function actingAsSuperAdminForSessionResource(): User
{
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole('super_admin');

    actingAs($user);

    return $user;
}

describe('ListSessions', function () {
    it('can load the page', function () {
        actingAsSuperAdminForSessionResource();

        Livewire::test(ListSessions::class)
            ->assertOk();
    });

    it('can list sessions', function () {
        actingAsSuperAdminForSessionResource();

        $sessions = DemocraticTimeSession::factory()->count(3)->create();

        Livewire::test(ListSessions::class)
            ->assertCanSeeTableRecords($sessions);
    });

    it('shows translated status and sign-in status labels', function () {
        actingAsSuperAdminForSessionResource();
        $this->freezeSecond();

        app()->setLocale('en');
        $session = DemocraticTimeSession::factory()->create([
            'started_at' => now()->subHour(),
            'ended_at' => now()->addHour(),
            'sign_in_opens_at' => null,
            'sign_in_closes_at' => null,
        ]);

        Livewire::test(ListSessions::class)
            ->assertTableColumnStateSet('status', 'ongoing', $session)
            ->assertTableColumnFormattedStateSet('status', 'Ongoing', $session)
            ->assertTableColumnStateSet('sign_in_status', 'not_opened', $session)
            ->assertTableColumnFormattedStateSet('sign_in_status', 'not opened yet', $session);
    });

    it('can search sessions by location', function () {
        actingAsSuperAdminForSessionResource();

        $sessions = DemocraticTimeSession::factory()->count(3)->create();

        Livewire::test(ListSessions::class)
            ->searchTable($sessions->first()->location)
            ->assertCanSeeTableRecords($sessions->take(1))
            ->assertCanNotSeeTableRecords($sessions->skip(1));
    });

    it('can search sessions by democratic time type name', function () {
        actingAsSuperAdminForSessionResource();

        $type = DemocraticTimeType::factory()->create(['name' => 'Special Council Meeting']);
        $session = DemocraticTimeSession::factory()->create(['democratic_time_type_id' => $type->id]);
        $otherSessions = DemocraticTimeSession::factory()->count(2)->create();

        Livewire::test(ListSessions::class)
            ->searchTable('Special Council')
            ->assertCanSeeTableRecords([$session])
            ->assertCanNotSeeTableRecords($otherSessions);
    });

    it('can sort sessions by ended_at', function () {
        actingAsSuperAdminForSessionResource();

        $middle = DemocraticTimeSession::factory()->create(['started_at' => '2026-01-01 08:00:00', 'ended_at' => '2026-03-01 10:00:00']);
        $latest = DemocraticTimeSession::factory()->create(['started_at' => '2026-01-01 08:00:00', 'ended_at' => '2026-05-01 10:00:00']);
        $earliest = DemocraticTimeSession::factory()->create(['started_at' => '2026-01-01 08:00:00', 'ended_at' => '2026-02-01 10:00:00']);

        Livewire::test(ListSessions::class)
            ->sortTable('ended_at')
            ->assertCanSeeTableRecords([$earliest, $middle, $latest], inOrder: true)
            ->sortTable('ended_at', 'desc')
            ->assertCanSeeTableRecords([$latest, $middle, $earliest], inOrder: true);
    });

    it('can sort sessions by started_at', function () {
        actingAsSuperAdminForSessionResource();

        $middle = DemocraticTimeSession::factory()->create(['started_at' => '2026-03-01 10:00:00', 'ended_at' => '2026-03-01 12:00:00']);
        $latest = DemocraticTimeSession::factory()->create(['started_at' => '2026-05-01 10:00:00', 'ended_at' => '2026-05-01 12:00:00']);
        $earliest = DemocraticTimeSession::factory()->create(['started_at' => '2026-02-01 10:00:00', 'ended_at' => '2026-02-01 12:00:00']);

        Livewire::test(ListSessions::class)
            ->sortTable('started_at')
            ->assertCanSeeTableRecords([$earliest, $middle, $latest], inOrder: true)
            ->sortTable('started_at', 'desc')
            ->assertCanSeeTableRecords([$latest, $middle, $earliest], inOrder: true);
    });
});

describe('CreateSession', function () {
    it('can load the page', function () {
        actingAsSuperAdminForSessionResource();

        Livewire::test(CreateSession::class)
            ->assertOk();
    });

    it('can create a session', function () {
        actingAsSuperAdminForSessionResource();
        $this->travelTo('2026-01-15 12:00:00');

        $type = DemocraticTimeType::factory()->create();
        $startedAt = now()->addDays(1)->format('Y-m-d H:i:s');
        $endedAt = now()->addDays(1)->addHours(2)->format('Y-m-d H:i:s');
        $location = fake()->address();

        Livewire::test(CreateSession::class)
            ->fillForm([
                'democratic_time_type_id' => $type->id,
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
                'location' => $location,
                'auto_open_sign_in' => true,
                'minutes_before_start' => 15,
            ])
            ->call('create')
            ->assertNotified()
            ->assertRedirect();

        assertDatabaseHas(DemocraticTimeSession::class, [
            'democratic_time_type_id' => $type->id,
            'location' => $location,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'auto_open_sign_in' => true,
            'minutes_before_start' => 15,
        ]);
    });

    it('can create a session without ended_at', function () {
        actingAsSuperAdminForSessionResource();
        $this->travelTo('2026-01-15 12:00:00');

        $type = DemocraticTimeType::factory()->create();
        $startedAt = now()->addDays(1)->format('Y-m-d H:i:s');
        $location = fake()->address();

        Livewire::test(CreateSession::class)
            ->fillForm([
                'democratic_time_type_id' => $type->id,
                'started_at' => $startedAt,
                'ended_at' => null,
                'location' => $location,
                'auto_open_sign_in' => false,
                'minutes_before_start' => 30,
            ])
            ->call('create')
            ->assertNotified()
            ->assertRedirect();

        assertDatabaseHas(DemocraticTimeSession::class, [
            'democratic_time_type_id' => $type->id,
            'location' => $location,
            'started_at' => $startedAt,
            'ended_at' => null,
            'auto_open_sign_in' => false,
        ]);
    });

    it('validates the form data', function (array $data, array $errors) {
        actingAsSuperAdminForSessionResource();

        $type = DemocraticTimeType::factory()->create();
        $startedAt = '2026-02-01 10:00:00';
        $location = fake()->address();

        Livewire::test(CreateSession::class)
            ->fillForm([
                'democratic_time_type_id' => $type->id,
                'started_at' => $startedAt,
                'location' => $location,
                'auto_open_sign_in' => true,
                'minutes_before_start' => 15,
                ...$data,
            ])
            ->call('create')
            ->assertHasFormErrors($errors)
            ->assertNotNotified()
            ->assertNoRedirect();
    })->with([
        '`democratic_time_type_id` is required' => [['democratic_time_type_id' => null], ['democratic_time_type_id' => 'required']],
        '`started_at` is required' => [['started_at' => null], ['started_at' => 'required']],
        '`location` is required' => [['location' => null], ['location' => 'required']],
        '`location` is max 255 characters' => [['location' => Str::random(256)], ['location' => 'max']],
        '`ended_at` must be after started_at' => [['ended_at' => '2026-01-31 10:00:00'], ['ended_at' => 'after']],
        '`minutes_before_start` must be numeric' => [['minutes_before_start' => 'invalid'], ['minutes_before_start' => 'numeric']],
        '`minutes_before_start` must be at least 0' => [['minutes_before_start' => -1], ['minutes_before_start' => 'min']],
        '`minutes_before_start` must be at most 120' => [['minutes_before_start' => 121], ['minutes_before_start' => 'max']],
    ]);

    it('shows the translated required message for location', function () {
        actingAsSuperAdminForSessionResource();

        Livewire::test(CreateSession::class)
            ->fillForm(['democratic_time_type_id' => DemocraticTimeType::factory()->create()->id, 'started_at' => '2026-02-01 10:00:00', 'auto_open_sign_in' => true, 'minutes_before_start' => 15, 'location' => null])
            ->call('create')
            ->assertHasFormErrors(['location' => [
                __('validation.required', ['attribute' => Str::lower(__('democratic_time_session.fields.location'))]),
            ]]);
    });

    it('shows the translated max message for location', function () {
        actingAsSuperAdminForSessionResource();

        Livewire::test(CreateSession::class)
            ->fillForm(['democratic_time_type_id' => DemocraticTimeType::factory()->create()->id, 'started_at' => '2026-02-01 10:00:00', 'auto_open_sign_in' => true, 'minutes_before_start' => 15, 'location' => Str::random(256)])
            ->call('create')
            ->assertHasFormErrors(['location' => [
                __('validation.max.string', ['attribute' => Str::lower(__('democratic_time_session.fields.location')), 'max' => 255]),
            ]]);
    });
});

describe('EditSession', function () {
    it('can load the page', function () {
        actingAsSuperAdminForSessionResource();

        $session = DemocraticTimeSession::factory()->create();

        Livewire::test(EditSession::class, [
            'record' => $session->id,
        ])
            ->assertOk()
            ->assertSchemaStateSet([
                'democratic_time_type_id' => $session->democratic_time_type_id,
                'location' => $session->location,
            ]);
    });

    it('can update a session', function () {
        actingAsSuperAdminForSessionResource();

        $session = DemocraticTimeSession::factory()->create();
        $newType = DemocraticTimeType::factory()->create();
        $newLocation = fake()->address();

        Livewire::test(EditSession::class, [
            'record' => $session->id,
        ])
            ->fillForm([
                'democratic_time_type_id' => $newType->id,
                'location' => $newLocation,
            ])
            ->call('save')
            ->assertNotified();

        assertDatabaseHas(DemocraticTimeSession::class, [
            'id' => $session->id,
            'democratic_time_type_id' => $newType->id,
            'location' => $newLocation,
        ]);
    });

    it('can update session dates', function () {
        actingAsSuperAdminForSessionResource();

        $session = DemocraticTimeSession::factory()->create();
        $newStartedAt = '2026-03-10 09:00:00';
        $newEndedAt = '2026-03-10 12:00:00';

        Livewire::test(EditSession::class, [
            'record' => $session->id,
        ])
            ->fillForm([
                'started_at' => $newStartedAt,
                'ended_at' => $newEndedAt,
            ])
            ->call('save')
            ->assertNotified();

        $session->refresh();

        expect($session->started_at->toDateTimeString())->toBe($newStartedAt)
            ->and($session->ended_at->toDateTimeString())->toBe($newEndedAt);
    });

    it('validates the form data', function (array $data, array $errors) {
        actingAsSuperAdminForSessionResource();

        $session = DemocraticTimeSession::factory()->create();

        Livewire::test(EditSession::class, [
            'record' => $session->id,
        ])
            ->fillForm($data)
            ->call('save')
            ->assertHasFormErrors($errors)
            ->assertNotNotified();
    })->with([
        '`democratic_time_type_id` is required' => [['democratic_time_type_id' => null], ['democratic_time_type_id' => 'required']],
        '`started_at` is required' => [['started_at' => null], ['started_at' => 'required']],
        '`location` is required' => [['location' => null], ['location' => 'required']],
        '`location` is max 255 characters' => [['location' => Str::random(256)], ['location' => 'max']],
    ]);

    it('can delete a session', function () {
        actingAsSuperAdminForSessionResource();

        $session = DemocraticTimeSession::factory()->create();

        Livewire::test(EditSession::class, [
            'record' => $session->id,
        ])
            ->callAction(DeleteAction::class)
            ->assertNotified()
            ->assertRedirect();

        assertModelMissing($session);
    });
});

describe('SessionActions', function () {
    it('opens sign in at the current time', function () {
        actingAsSuperAdminForSessionResource();
        $this->freezeSecond();

        $session = DemocraticTimeSession::factory()->create([
            'sign_in_opens_at' => null,
        ]);

        Livewire::test(ListSessions::class)
            ->callTableAction('open_sign_in', $session)
            ->assertNotified(__('emargement.messages.sign_in_opened'));

        expect($session->refresh()->sign_in_opens_at)->toEqual(now());
    });

    it('hides the open sign in action once sign in has been opened', function () {
        actingAsSuperAdminForSessionResource();
        $this->freezeSecond();

        $session = DemocraticTimeSession::factory()->create([
            'sign_in_opens_at' => now()->subHour(),
        ]);

        Livewire::test(ListSessions::class)
            ->assertTableActionHidden('open_sign_in', $session);
    });

    it('closes sign in at the current time when the session has no end', function () {
        actingAsSuperAdminForSessionResource();
        $this->freezeSecond();

        $session = DemocraticTimeSession::factory()->create([
            'ended_at' => null,
            'sign_in_opens_at' => now()->subHour(),
            'sign_in_closes_at' => null,
        ]);

        Livewire::test(ListSessions::class)
            ->callTableAction('close_sign_in', $session)
            ->assertNotified(__('emargement.messages.sign_in_closed'));

        expect($session->refresh()->sign_in_closes_at)->toEqual(now());
    });

    it('closes sign in at the session end time when the session has an end', function () {
        actingAsSuperAdminForSessionResource();
        $this->freezeSecond();

        $session = DemocraticTimeSession::factory()->create([
            'ended_at' => now()->addHours(2),
            'sign_in_opens_at' => now()->subHour(),
            'sign_in_closes_at' => null,
        ]);

        Livewire::test(ListSessions::class)
            ->callTableAction('close_sign_in', $session)
            ->assertNotified(__('emargement.messages.sign_in_closed'));

        expect($session->refresh()->sign_in_closes_at)->toEqual(now()->addHours(2));
    });

    it('hides the close sign in action when sign in is not open or already closed', function (?string $opensAt, ?string $closesAt) {
        actingAsSuperAdminForSessionResource();

        $session = DemocraticTimeSession::factory()->create([
            'sign_in_opens_at' => $opensAt,
            'sign_in_closes_at' => $closesAt,
        ]);

        Livewire::test(ListSessions::class)
            ->assertTableActionHidden('close_sign_in', $session);
    })->with([
        'sign in never opened' => [null, null],
        'sign in already closed' => ['2026-01-01 08:00:00', '2026-01-01 10:00:00'],
    ]);
});

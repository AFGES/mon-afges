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

    it('can sort sessions by started_at', function () {
        actingAsSuperAdminForSessionResource();

        $sessions = DemocraticTimeSession::factory()->count(3)->create();

        Livewire::test(ListSessions::class)
            ->sortTable('started_at')
            ->assertCanSeeTableRecords($sessions->sortBy('started_at'), inOrder: true)
            ->sortTable('started_at', 'desc')
            ->assertCanSeeTableRecords($sessions->sortByDesc('started_at'), inOrder: true);
    });

    it('can sort sessions by ended_at', function () {
        actingAsSuperAdminForSessionResource();

        $sessions = DemocraticTimeSession::factory()->count(3)->create();

        Livewire::test(ListSessions::class)
            ->sortTable('ended_at')
            ->assertCanSeeTableRecords($sessions->sortBy('ended_at'), inOrder: true)
            ->sortTable('ended_at', 'desc')
            ->assertCanSeeTableRecords($sessions->sortByDesc('ended_at'), inOrder: true);
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
            'auto_open_sign_in' => true,
            'minutes_before_start' => 15,
        ]);
    });

    it('can create a session without ended_at', function () {
        actingAsSuperAdminForSessionResource();

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
            'auto_open_sign_in' => false,
        ]);
    });

    it('validates the form data', function (array $data, array $errors) {
        actingAsSuperAdminForSessionResource();

        $type = DemocraticTimeType::factory()->create();
        $startedAt = now()->addDays(1)->format('Y-m-d H:i:s');
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
        '`ended_at` must be after started_at' => [['ended_at' => now()->subDays(1)->format('Y-m-d H:i:s')], ['ended_at' => 'after']],
        '`minutes_before_start` must be numeric' => [['minutes_before_start' => 'invalid'], ['minutes_before_start' => 'numeric']],
        '`minutes_before_start` must be at least 0' => [['minutes_before_start' => -1], ['minutes_before_start' => 'min']],
        '`minutes_before_start` must be at most 120' => [['minutes_before_start' => 121], ['minutes_before_start' => 'max']],
    ]);
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
        $newStartedAt = now()->addDays(7)->format('Y-m-d H:i:s');
        $newEndedAt = now()->addDays(7)->addHours(3)->format('Y-m-d H:i:s');

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
        expect($session->started_at->format('Y-m-d H:i'))->toBe(date('Y-m-d H:i', strtotime($newStartedAt)));
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
    it('can open sign in', function () {
        actingAsSuperAdminForSessionResource();

        $session = DemocraticTimeSession::factory()->create([
            'sign_in_opens_at' => null,
        ]);

        Livewire::test(ListSessions::class)
            ->callTableAction('open_sign_in', $session)
            ->assertNotified();

        $session->refresh();
        expect($session->sign_in_opens_at)->not()->toBeNull();
    });

    it('can close sign in', function () {
        actingAsSuperAdminForSessionResource();

        $session = DemocraticTimeSession::factory()->create([
            'sign_in_opens_at' => now()->subHour(),
            'sign_in_closes_at' => null,
        ]);

        Livewire::test(ListSessions::class)
            ->callTableAction('close_sign_in', $session)
            ->assertNotified();

        $session->refresh();
        expect($session->sign_in_closes_at)->not()->toBeNull();
    });
});

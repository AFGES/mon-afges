<?php

declare(strict_types=1);

use App\Filament\Clusters\DemocraticTimes\Resources\Sessions\Pages\ManageSessionProxies;
use App\Filament\Clusters\DemocraticTimes\Resources\Sessions\Pages\ManageSessionSignIns;
use App\Models\Delegate;
use App\Models\DemocraticTimeSession;
use App\Models\Proxy;
use App\Models\Seat;
use App\Models\SessionSignIn;
use App\Models\User;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin']);
    $this->actingAsUser = User::factory()->create();
    $this->actingAsUser->assignRole('super_admin');
    $this->actingAs($this->actingAsUser);
});

describe('ProxiesRelationManager', function () {
    it('can render the relation manager', function () {
        $session = DemocraticTimeSession::factory()->create();

        livewire(ManageSessionProxies::class, [
            'record' => $session->getKey(),
        ])
            ->assertOk();
    });

    it('can list proxies for a session', function () {
        $session = DemocraticTimeSession::factory()->create();
        $proxies = Proxy::factory()->count(3)->for($session)->create();
        $otherSessionProxy = Proxy::factory()->create();

        livewire(ManageSessionProxies::class, [
            'record' => $session->getKey(),
        ])
            ->assertCanSeeTableRecords($proxies)
            ->assertCanNotSeeTableRecords([$otherSessionProxy]);
    });

    it('can create a proxy for a session', function () {
        $session = DemocraticTimeSession::factory()->create();

        // Create seats from the same college (required by validation rule)
        $college = App\Models\College::factory()->create();
        $organization = App\Models\Organization::factory()->create();
        $collegeOrganization = App\Models\CollegeOrganization::factory()->create([
            'college_id' => $college->id,
            'organization_id' => $organization->id,
        ]);

        $delegatorSeat = Seat::factory()->create([
            'college_organization_id' => $collegeOrganization->id,
        ]);
        $proxyHolderSeat = Seat::factory()->create([
            'college_organization_id' => $collegeOrganization->id,
        ]);

        $startedAt = now()->toDateTimeString();

        livewire(ManageSessionProxies::class, [
            'record' => $session->getKey(),
        ])
            ->callTableAction('create', data: [
                'democratic_time_session_id' => $session->id,
                'delegator_seat_id' => $delegatorSeat->id,
                'proxy_holder_seat_id' => $proxyHolderSeat->id,
                'started_at' => $startedAt,
            ])
            ->assertNotified();

        assertDatabaseHas(Proxy::class, [
            'democratic_time_session_id' => $session->id,
            'delegator_seat_id' => $delegatorSeat->id,
            'proxy_holder_seat_id' => $proxyHolderSeat->id,
        ]);
    });
});

describe('SessionSignInsRelationManager', function () {
    it('can render the relation manager', function () {
        $session = DemocraticTimeSession::factory()->create();

        livewire(ManageSessionSignIns::class, [
            'record' => $session->getKey(),
        ])
            ->assertOk();
    });

    it('can list sign-ins for a session', function () {
        $session = DemocraticTimeSession::factory()->create();
        $signIns = SessionSignIn::factory()->count(3)->for($session)->create();
        $otherSessionSignIn = SessionSignIn::factory()->create();

        livewire(ManageSessionSignIns::class, [
            'record' => $session->getKey(),
        ])
            ->assertCanSeeTableRecords($signIns)
            ->assertCanNotSeeTableRecords([$otherSessionSignIn]);
    });

    it('can create a sign-in for a session', function () {
        $session = DemocraticTimeSession::factory()->create();
        $seat = Seat::factory()->create();
        $delegate = Delegate::factory()->for($seat)->approved()->create();
        $signedInAt = now()->toDateTimeString();

        livewire(ManageSessionSignIns::class, [
            'record' => $session->getKey(),
        ])
            ->callTableAction('create', data: [
                'democratic_time_session_id' => $session->id,
                'seat_id' => $seat->id,
                'delegate_id' => $delegate->id,
                'signed_in_at' => $signedInAt,
            ])
            ->assertNotified();

        assertDatabaseHas(SessionSignIn::class, [
            'democratic_time_session_id' => $session->id,
            'seat_id' => $seat->id,
            'delegate_id' => $delegate->id,
        ]);
    });

    it('can filter signed-in delegates', function () {
        $session = DemocraticTimeSession::factory()->create();
        $signedIn = SessionSignIn::factory()->for($session)->create([
            'signed_in_at' => now(),
            'signed_out_at' => null,
        ]);
        $signedOut = SessionSignIn::factory()->for($session)->create([
            'signed_in_at' => now()->subHour(),
            'signed_out_at' => now(),
        ]);

        livewire(ManageSessionSignIns::class, [
            'record' => $session->getKey(),
        ])
            ->filterTable('signed_in')
            ->assertCanSeeTableRecords([$signedIn])
            ->assertCanNotSeeTableRecords([$signedOut]);
    });

    it('can filter signed-out delegates', function () {
        $session = DemocraticTimeSession::factory()->create();
        $signedIn = SessionSignIn::factory()->for($session)->create([
            'signed_in_at' => now(),
            'signed_out_at' => null,
        ]);
        $signedOut = SessionSignIn::factory()->for($session)->create([
            'signed_in_at' => now()->subHour(),
            'signed_out_at' => now(),
        ]);

        livewire(ManageSessionSignIns::class, [
            'record' => $session->getKey(),
        ])
            ->filterTable('signed_out')
            ->assertCanSeeTableRecords([$signedOut])
            ->assertCanNotSeeTableRecords([$signedIn]);
    });
});

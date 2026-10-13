<?php

declare(strict_types=1);

use App\Filament\Clusters\DemocraticTimes\Resources\Proxies\Pages\CreateProxy;
use App\Filament\Clusters\DemocraticTimes\Resources\Proxies\Pages\EditProxy;
use App\Filament\Clusters\DemocraticTimes\Resources\Proxies\Pages\ListProxies;
use App\Models\College;
use App\Models\CollegeOrganization;
use App\Models\DemocraticTimeSession;
use App\Models\Proxy;
use App\Models\Seat;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertModelMissing;
use function Pest\Livewire\livewire;

function actingAsSuperAdminForProxyResource(): User
{
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole('super_admin');

    actingAs($user);

    return $user;
}

describe('ListProxies', function () {
    it('can load the page', function () {
        actingAsSuperAdminForProxyResource();

        livewire(ListProxies::class)
            ->assertOk();
    });

    it('can list proxies', function () {
        actingAsSuperAdminForProxyResource();

        $proxies = Proxy::factory()->count(3)->approved()->create();

        livewire(ListProxies::class)
            ->assertCanSeeTableRecords($proxies);
    });

    it('displays proxy delegator seat information', function () {
        actingAsSuperAdminForProxyResource();

        $college = College::factory()->create(['name' => 'Test College']);
        $collegeOrg = CollegeOrganization::factory()->create(['college_id' => $college->id]);
        $seat = Seat::factory()->create(['college_organization_id' => $collegeOrg->id]);
        $proxyHolderSeat = Seat::factory()->create(['college_organization_id' => $collegeOrg->id]);
        $proxy = Proxy::factory()->approved()->create([
            'delegator_seat_id' => $seat->id,
            'proxy_holder_seat_id' => $proxyHolderSeat->id,
        ]);

        livewire(ListProxies::class)
            ->assertCanSeeTableRecords([$proxy])
            ->assertTableColumnStateSet('delegatorSeat.label', $seat->label(), $proxy)
            ->assertTableColumnStateSet('proxyHolderSeat.label', $proxyHolderSeat->label(), $proxy);
    });
});

describe('CreateProxy', function () {
    it('can load the page', function () {
        actingAsSuperAdminForProxyResource();

        livewire(CreateProxy::class)
            ->assertOk();
    });

    it('can create a proxy with valid data', function () {
        actingAsSuperAdminForProxyResource();
        $this->freezeSecond();

        $college = College::factory()->create([
            'can_vote' => true,
            'require_same_college_for_proxy' => true,
        ]);
        $collegeOrg = CollegeOrganization::factory()->create(['college_id' => $college->id]);
        $delegatorSeat = Seat::factory()->create(['college_organization_id' => $collegeOrg->id, 'seat_number' => 1]);
        $proxyHolderSeat = Seat::factory()->create(['college_organization_id' => $collegeOrg->id, 'seat_number' => 2]);
        $session = DemocraticTimeSession::factory()->create();

        livewire(CreateProxy::class)
            ->fillForm([
                'delegator_seat_id' => $delegatorSeat->id,
                'proxy_holder_seat_id' => $proxyHolderSeat->id,
                'democratic_time_session_id' => $session->id,
                'started_at' => now()->toDateTimeString(),
                'ended_at' => now()->addHours(2)->toDateTimeString(),
            ])
            ->call('create')
            ->assertNotified()
            ->assertRedirect();

        assertDatabaseHas(Proxy::class, [
            'delegator_seat_id' => $delegatorSeat->id,
            'proxy_holder_seat_id' => $proxyHolderSeat->id,
            'democratic_time_session_id' => $session->id,
            'started_at' => now(),
            'ended_at' => now()->addHours(2),
        ]);
    });

    it('can create a proxy without start and end times', function () {
        actingAsSuperAdminForProxyResource();

        $college = College::factory()->create([
            'can_vote' => true,
            'require_same_college_for_proxy' => true,
        ]);
        $collegeOrg = CollegeOrganization::factory()->create(['college_id' => $college->id]);
        $delegatorSeat = Seat::factory()->create(['college_organization_id' => $collegeOrg->id, 'seat_number' => 1]);
        $proxyHolderSeat = Seat::factory()->create(['college_organization_id' => $collegeOrg->id, 'seat_number' => 2]);
        $session = DemocraticTimeSession::factory()->create();

        livewire(CreateProxy::class)
            ->fillForm([
                'delegator_seat_id' => $delegatorSeat->id,
                'proxy_holder_seat_id' => $proxyHolderSeat->id,
                'democratic_time_session_id' => $session->id,
            ])
            ->call('create')
            ->assertNotified()
            ->assertRedirect();

        assertDatabaseHas(Proxy::class, [
            'delegator_seat_id' => $delegatorSeat->id,
            'proxy_holder_seat_id' => $proxyHolderSeat->id,
            'democratic_time_session_id' => $session->id,
        ]);
    });

    it('validates required delegator_seat_id', function () {
        actingAsSuperAdminForProxyResource();

        $college = College::factory()->create(['can_vote' => true]);
        $collegeOrg = CollegeOrganization::factory()->create(['college_id' => $college->id]);
        $proxyHolderSeat = Seat::factory()->create(['college_organization_id' => $collegeOrg->id]);
        $session = DemocraticTimeSession::factory()->create();

        livewire(CreateProxy::class)
            ->fillForm([
                'delegator_seat_id' => null,
                'proxy_holder_seat_id' => $proxyHolderSeat->id,
                'democratic_time_session_id' => $session->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['delegator_seat_id' => [
                __('validation.required', ['attribute' => Str::lower(__('proxy.fields.delegator_seat'))]),
            ]])
            ->assertNotNotified()
            ->assertNoRedirect();
    });

    it('validates required proxy_holder_seat_id', function () {
        actingAsSuperAdminForProxyResource();

        $college = College::factory()->create(['can_vote' => true]);
        $collegeOrg = CollegeOrganization::factory()->create(['college_id' => $college->id]);
        $delegatorSeat = Seat::factory()->create(['college_organization_id' => $collegeOrg->id, 'seat_number' => 1]);
        $session = DemocraticTimeSession::factory()->create();

        livewire(CreateProxy::class)
            ->fillForm([
                'delegator_seat_id' => $delegatorSeat->id,
                'proxy_holder_seat_id' => null,
                'democratic_time_session_id' => $session->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['proxy_holder_seat_id' => 'required'])
            ->assertNotNotified()
            ->assertNoRedirect();
    });

    it('validates required democratic_time_session_id', function () {
        actingAsSuperAdminForProxyResource();

        $college = College::factory()->create(['can_vote' => true]);
        $collegeOrg = CollegeOrganization::factory()->create(['college_id' => $college->id]);
        $delegatorSeat = Seat::factory()->create(['college_organization_id' => $collegeOrg->id, 'seat_number' => 1]);
        $proxyHolderSeat = Seat::factory()->create(['college_organization_id' => $collegeOrg->id, 'seat_number' => 2]);

        livewire(CreateProxy::class)
            ->fillForm([
                'delegator_seat_id' => $delegatorSeat->id,
                'proxy_holder_seat_id' => $proxyHolderSeat->id,
                'democratic_time_session_id' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['democratic_time_session_id' => 'required'])
            ->assertNotNotified()
            ->assertNoRedirect();
    });

    it('validates that proxy holder seat must be from same college when required', function () {
        actingAsSuperAdminForProxyResource();

        $college1 = College::factory()->create([
            'can_vote' => true,
            'require_same_college_for_proxy' => true,
        ]);
        $college2 = College::factory()->create([
            'can_vote' => true,
            'require_same_college_for_proxy' => false,
        ]);
        $collegeOrg1 = CollegeOrganization::factory()->create(['college_id' => $college1->id]);
        $collegeOrg2 = CollegeOrganization::factory()->create(['college_id' => $college2->id]);
        $delegatorSeat = Seat::factory()->create(['college_organization_id' => $collegeOrg1->id, 'seat_number' => 1]);
        $proxyHolderSeat = Seat::factory()->create(['college_organization_id' => $collegeOrg2->id, 'seat_number' => 1]);
        $session = DemocraticTimeSession::factory()->create();

        livewire(CreateProxy::class)
            ->fillForm([
                'delegator_seat_id' => $delegatorSeat->id,
                'proxy_holder_seat_id' => $proxyHolderSeat->id,
                'democratic_time_session_id' => $session->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['proxy_holder_seat_id' => [__('proxy.validation.same_college')]])
            ->assertNotNotified()
            ->assertNoRedirect();
    });

    it('allows cross-college proxy when not required to be same college', function () {
        actingAsSuperAdminForProxyResource();

        $college1 = College::factory()->create([
            'can_vote' => true,
            'require_same_college_for_proxy' => false,
        ]);
        $college2 = College::factory()->create([
            'can_vote' => true,
        ]);
        $collegeOrg1 = CollegeOrganization::factory()->create(['college_id' => $college1->id]);
        $collegeOrg2 = CollegeOrganization::factory()->create(['college_id' => $college2->id]);
        $delegatorSeat = Seat::factory()->create(['college_organization_id' => $collegeOrg1->id, 'seat_number' => 1]);
        $proxyHolderSeat = Seat::factory()->create(['college_organization_id' => $collegeOrg2->id, 'seat_number' => 1]);
        $session = DemocraticTimeSession::factory()->create();

        livewire(CreateProxy::class)
            ->fillForm([
                'delegator_seat_id' => $delegatorSeat->id,
                'proxy_holder_seat_id' => $proxyHolderSeat->id,
                'democratic_time_session_id' => $session->id,
            ])
            ->call('create')
            ->assertNotified()
            ->assertRedirect();

        assertDatabaseHas(Proxy::class, [
            'delegator_seat_id' => $delegatorSeat->id,
            'proxy_holder_seat_id' => $proxyHolderSeat->id,
            'democratic_time_session_id' => $session->id,
        ]);
    });

    it('validates that proxy holder college can vote for cross-college proxies', function () {
        actingAsSuperAdminForProxyResource();

        $college1 = College::factory()->create([
            'can_vote' => true,
            'require_same_college_for_proxy' => false,
        ]);
        $college2 = College::factory()->create([
            'can_vote' => false, // Non-voting college
        ]);
        $collegeOrg1 = CollegeOrganization::factory()->create(['college_id' => $college1->id]);
        $collegeOrg2 = CollegeOrganization::factory()->create(['college_id' => $college2->id]);
        $delegatorSeat = Seat::factory()->create(['college_organization_id' => $collegeOrg1->id, 'seat_number' => 1]);
        $proxyHolderSeat = Seat::factory()->create(['college_organization_id' => $collegeOrg2->id, 'seat_number' => 1]);
        $session = DemocraticTimeSession::factory()->create();

        livewire(CreateProxy::class)
            ->fillForm([
                'delegator_seat_id' => $delegatorSeat->id,
                'proxy_holder_seat_id' => $proxyHolderSeat->id,
                'democratic_time_session_id' => $session->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['proxy_holder_seat_id' => ['Le siège mandataire doit appartenir à un collège délibérant.']])
            ->assertNotNotified()
            ->assertNoRedirect();
    });
});

describe('EditProxy', function () {
    it('can load the page', function () {
        actingAsSuperAdminForProxyResource();

        $proxy = Proxy::factory()->approved()->create();

        livewire(EditProxy::class, [
            'record' => $proxy->id,
        ])
            ->assertOk()
            ->assertSchemaStateSet([
                'delegator_seat_id' => $proxy->delegator_seat_id,
                'proxy_holder_seat_id' => $proxy->proxy_holder_seat_id,
                'democratic_time_session_id' => $proxy->democratic_time_session_id,
            ]);
    });

    it('can update a proxy', function () {
        actingAsSuperAdminForProxyResource();

        $college = College::factory()->create([
            'can_vote' => true,
            'require_same_college_for_proxy' => true,
        ]);
        $collegeOrg = CollegeOrganization::factory()->create(['college_id' => $college->id]);
        $proxy = Proxy::factory()->approved()->create([
            'delegator_seat_id' => Seat::factory()->create(['college_organization_id' => $collegeOrg->id, 'seat_number' => 1])->id,
            'proxy_holder_seat_id' => Seat::factory()->create(['college_organization_id' => $collegeOrg->id, 'seat_number' => 2])->id,
        ]);
        $newSession = DemocraticTimeSession::factory()->create();

        livewire(EditProxy::class, [
            'record' => $proxy->id,
        ])
            ->fillForm([
                'democratic_time_session_id' => $newSession->id,
            ])
            ->call('save')
            ->assertNotified();

        assertDatabaseHas(Proxy::class, [
            'id' => $proxy->id,
            'democratic_time_session_id' => $newSession->id,
        ]);
    });

    it('can update proxy start and end times', function () {
        actingAsSuperAdminForProxyResource();
        $this->freezeSecond();

        $college = College::factory()->create(['can_vote' => true]);
        $collegeOrg = CollegeOrganization::factory()->create(['college_id' => $college->id]);
        $delegatorSeat = Seat::factory()->create(['college_organization_id' => $collegeOrg->id, 'seat_number' => 1]);
        $proxyHolderSeat = Seat::factory()->create(['college_organization_id' => $collegeOrg->id, 'seat_number' => 2]);
        $session = DemocraticTimeSession::factory()->create();

        $proxy = Proxy::factory()->approved()->create([
            'delegator_seat_id' => $delegatorSeat->id,
            'proxy_holder_seat_id' => $proxyHolderSeat->id,
            'democratic_time_session_id' => $session->id,
            'started_at' => now(),
            'ended_at' => now()->addHour(),
        ]);

        $newStartTime = now()->addDay();
        $newEndTime = now()->addDays(2);

        livewire(EditProxy::class, [
            'record' => $proxy->id,
        ])
            ->fillForm([
                'started_at' => $newStartTime->toDateTimeString(),
                'ended_at' => $newEndTime->toDateTimeString(),
            ])
            ->call('save')
            ->assertNotified();

        $proxy->refresh();

        expect($proxy->started_at)->toEqual($newStartTime)
            ->and($proxy->ended_at)->toEqual($newEndTime);
    });

    it('can delete a proxy', function () {
        actingAsSuperAdminForProxyResource();

        $proxy = Proxy::factory()->approved()->create();

        livewire(EditProxy::class, [
            'record' => $proxy->id,
        ])
            ->callAction('delete')
            ->assertNotified();

        assertModelMissing($proxy);
    });

    it('validates same college rule when updating proxy holder seat', function () {
        actingAsSuperAdminForProxyResource();

        $college1 = College::factory()->create([
            'can_vote' => true,
            'require_same_college_for_proxy' => true,
        ]);
        $college2 = College::factory()->create([
            'can_vote' => true,
        ]);
        $collegeOrg1 = CollegeOrganization::factory()->create(['college_id' => $college1->id]);
        $collegeOrg2 = CollegeOrganization::factory()->create(['college_id' => $college2->id]);
        $delegatorSeat = Seat::factory()->create(['college_organization_id' => $collegeOrg1->id, 'seat_number' => 1]);
        $initialProxyHolderSeat = Seat::factory()->create(['college_organization_id' => $collegeOrg1->id, 'seat_number' => 2]);
        $newProxyHolderSeat = Seat::factory()->create(['college_organization_id' => $collegeOrg2->id, 'seat_number' => 1]);

        $proxy = Proxy::factory()->approved()->create([
            'delegator_seat_id' => $delegatorSeat->id,
            'proxy_holder_seat_id' => $initialProxyHolderSeat->id,
        ]);

        livewire(EditProxy::class, [
            'record' => $proxy->id,
        ])
            ->fillForm([
                'proxy_holder_seat_id' => $newProxyHolderSeat->id,
            ])
            ->call('save')
            ->assertHasFormErrors(['proxy_holder_seat_id' => [__('proxy.validation.same_college')]])
            ->assertNotNotified();
    });

    it('validates required fields when updating', function (array $data, array $errors) {
        actingAsSuperAdminForProxyResource();

        $proxy = Proxy::factory()->approved()->create();

        livewire(EditProxy::class, [
            'record' => $proxy->id,
        ])
            ->fillForm($data)
            ->call('save')
            ->assertHasFormErrors($errors)
            ->assertNotNotified();
    })->with([
        'delegator_seat_id is required' => [
            ['delegator_seat_id' => null],
            ['delegator_seat_id' => 'required'],
        ],
        'proxy_holder_seat_id is required' => [
            ['proxy_holder_seat_id' => null],
            ['proxy_holder_seat_id' => 'required'],
        ],
        'democratic_time_session_id is required' => [
            ['democratic_time_session_id' => null],
            ['democratic_time_session_id' => 'required'],
        ],
    ]);
});

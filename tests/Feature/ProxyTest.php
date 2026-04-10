<?php

declare(strict_types=1);

use App\Models\College;
use App\Models\CollegeOrganization;
use App\Models\DemocraticTimeSession;
use App\Models\Organization;
use App\Models\Proxy;
use App\Models\Seat;
use App\Rules\ProxyDelegatesFromSameCollege;
use Illuminate\Support\Facades\Validator;

test('proxy can be created with all required fields', function () {
    $delegatorSeat = Seat::factory()->create();
    $proxyHolderSeat = Seat::factory()->create();
    $session = DemocraticTimeSession::factory()->create();

    $proxy = Proxy::factory()->create([
        'delegator_seat_id' => $delegatorSeat->id,
        'proxy_holder_seat_id' => $proxyHolderSeat->id,
        'democratic_time_session_id' => $session->id,
        'started_at' => null,
        'ended_at' => null,
    ]);

    expect($proxy->delegator_seat_id)->toBe($delegatorSeat->id)
        ->and($proxy->proxy_holder_seat_id)->toBe($proxyHolderSeat->id)
        ->and($proxy->democratic_time_session_id)->toBe($session->id)
        ->and($proxy->started_at)->toBeNull()
        ->and($proxy->ended_at)->toBeNull();
});

test('proxy belongs to delegator seat', function () {
    $delegatorSeat = Seat::factory()->create();
    $proxy = Proxy::factory()->create(['delegator_seat_id' => $delegatorSeat->id]);

    expect($proxy->delegatorSeat->id)->toBe($delegatorSeat->id);
});

test('proxy belongs to proxy holder seat', function () {
    $proxyHolderSeat = Seat::factory()->create();
    $proxy = Proxy::factory()->create(['proxy_holder_seat_id' => $proxyHolderSeat->id]);

    expect($proxy->proxyHolderSeat->id)->toBe($proxyHolderSeat->id);
});

test('proxy belongs to democratic time session', function () {
    $session = DemocraticTimeSession::factory()->create();
    $proxy = Proxy::factory()->create(['democratic_time_session_id' => $session->id]);

    expect($proxy->democraticTimeSession->id)->toBe($session->id);
});

test('seat can give multiple proxies for different sessions', function () {
    $delegatorSeat = Seat::factory()->create();
    $session1 = DemocraticTimeSession::factory()->create();
    $session2 = DemocraticTimeSession::factory()->create();

    Proxy::factory()->approved()->create([
        'delegator_seat_id' => $delegatorSeat->id,
        'democratic_time_session_id' => $session1->id,
    ]);
    Proxy::factory()->approved()->create([
        'delegator_seat_id' => $delegatorSeat->id,
        'democratic_time_session_id' => $session2->id,
    ]);

    expect($delegatorSeat->proxiesGiven()->count())->toBe(2);
});

test('seat can receive multiple proxies for same session', function () {
    $proxyHolderSeat = Seat::factory()->create();
    $session = DemocraticTimeSession::factory()->create();

    Proxy::factory()->approved()->create([
        'proxy_holder_seat_id' => $proxyHolderSeat->id,
        'democratic_time_session_id' => $session->id,
    ]);
    Proxy::factory()->approved()->create([
        'proxy_holder_seat_id' => $proxyHolderSeat->id,
        'democratic_time_session_id' => $session->id,
    ]);

    expect($proxyHolderSeat->proxiesReceived()->count())->toBe(2);
});

test('proxy can be for full session', function () {
    $proxy = Proxy::factory()->fullSession()->create();

    expect($proxy->started_at)->toBeNull()
        ->and($proxy->ended_at)->toBeNull()
        ->and($proxy->isActiveAt(now()))->toBeTrue();
});

test('proxy can be from beginning to specific moment', function () {
    $endTime = now()->addHours(2);
    $proxy = Proxy::factory()->create([
        'started_at' => null,
        'ended_at' => $endTime,
    ]);

    expect($proxy->started_at)->toBeNull()
        ->and($proxy->ended_at)->not->toBeNull()
        ->and($proxy->isActiveAt(now()))->toBeTrue()
        ->and($proxy->isActiveAt($endTime->copy()->addMinute()))->toBeFalse();
});

test('proxy can be from specific moment to end', function () {
    $startTime = now()->addHours(1);
    $proxy = Proxy::factory()->create([
        'started_at' => $startTime,
        'ended_at' => null,
    ]);

    expect($proxy->started_at)->not->toBeNull()
        ->and($proxy->ended_at)->toBeNull()
        ->and($proxy->isActiveAt(now()))->toBeFalse()
        ->and($proxy->isActiveAt($startTime->copy()->addMinute()))->toBeTrue();
});

test('proxy can be between two specific moments', function () {
    $startTime = now()->addHours(1);
    $endTime = now()->addHours(3);
    $proxy = Proxy::factory()->create([
        'started_at' => $startTime,
        'ended_at' => $endTime,
    ]);

    expect($proxy->isActiveAt(now()))->toBeFalse()
        ->and($proxy->isActiveAt($startTime->copy()->addMinutes(30)))->toBeTrue()
        ->and($proxy->isActiveAt($endTime->copy()->addMinute()))->toBeFalse();
});

test('proxy times can be updated', function () {
    $proxy = Proxy::factory()->create([
        'started_at' => null,
        'ended_at' => null,
    ]);

    $newStart = now()->addHours(1);
    $newEnd = now()->addHours(2);

    $proxy->update([
        'started_at' => $newStart,
        'ended_at' => $newEnd,
    ]);

    expect($proxy->fresh()->started_at->timestamp)->toBe($newStart->timestamp)
        ->and($proxy->fresh()->ended_at->timestamp)->toBe($newEnd->timestamp);
});

test('seats from same college can be verified', function () {
    $organization = Organization::factory()->create();
    $college = College::factory()->create();
    $collegeOrg = CollegeOrganization::factory()->create([
        'organization_id' => $organization->id,
        'college_id' => $college->id,
    ]);

    $seat1 = Seat::factory()->create([
        'college_organization_id' => $collegeOrg->id,
        'seat_number' => 1,
    ]);
    $seat2 = Seat::factory()->create([
        'college_organization_id' => $collegeOrg->id,
        'seat_number' => 2,
    ]);

    $proxy = Proxy::factory()->create([
        'delegator_seat_id' => $seat1->id,
        'proxy_holder_seat_id' => $seat2->id,
    ]);

    expect($proxy->areSeatsFromSameCollege())->toBeTrue();
});

test('seats from different colleges can be detected', function () {
    $organization = Organization::factory()->create();
    $college1 = College::factory()->create();
    $college2 = College::factory()->create();

    $collegeOrg1 = CollegeOrganization::factory()->create([
        'organization_id' => $organization->id,
        'college_id' => $college1->id,
    ]);
    $collegeOrg2 = CollegeOrganization::factory()->create([
        'organization_id' => $organization->id,
        'college_id' => $college2->id,
    ]);

    $seat1 = Seat::factory()->create(['college_organization_id' => $collegeOrg1->id]);
    $seat2 = Seat::factory()->create(['college_organization_id' => $collegeOrg2->id]);

    $proxy = Proxy::factory()->create([
        'delegator_seat_id' => $seat1->id,
        'proxy_holder_seat_id' => $seat2->id,
    ]);

    expect($proxy->areSeatsFromSameCollege())->toBeFalse();
});

test('validation rule passes for seats from same college', function () {
    $organization = Organization::factory()->create();
    $college = College::factory()->create([
        'can_vote' => true,
        'require_same_college_for_proxy' => false,
    ]);
    $collegeOrg = CollegeOrganization::factory()->create([
        'organization_id' => $organization->id,
        'college_id' => $college->id,
    ]);

    $seat1 = Seat::factory()->create([
        'college_organization_id' => $collegeOrg->id,
        'seat_number' => 1,
    ]);
    $seat2 = Seat::factory()->create([
        'college_organization_id' => $collegeOrg->id,
        'seat_number' => 2,
    ]);

    $validator = Validator::make(
        ['proxy_holder_seat_id' => $seat2->id],
        ['proxy_holder_seat_id' => [new ProxyDelegatesFromSameCollege($seat1->id)]]
    );

    expect($validator->passes())->toBeTrue();
});

test('validation rule fails for seats from different colleges when require_same_college_for_proxy is true', function () {
    $organization = Organization::factory()->create();
    $college1 = College::factory()->create(['require_same_college_for_proxy' => true]);
    $college2 = College::factory()->create(['require_same_college_for_proxy' => true]);

    $collegeOrg1 = CollegeOrganization::factory()->create([
        'organization_id' => $organization->id,
        'college_id' => $college1->id,
    ]);
    $collegeOrg2 = CollegeOrganization::factory()->create([
        'organization_id' => $organization->id,
        'college_id' => $college2->id,
    ]);

    $seat1 = Seat::factory()->create(['college_organization_id' => $collegeOrg1->id]);
    $seat2 = Seat::factory()->create(['college_organization_id' => $collegeOrg2->id]);

    $validator = Validator::make(
        ['proxy_holder_seat_id' => $seat2->id],
        ['proxy_holder_seat_id' => [new ProxyDelegatesFromSameCollege($seat1->id)]]
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('proxy_holder_seat_id'))
        ->toBe('Le siège mandataire doit appartenir au même collège que le siège délégateur.');
});

test('validation rule passes for cross-college proxy when require_same_college_for_proxy is false', function () {
    $organization = Organization::factory()->create();
    $college1 = College::factory()->create(['require_same_college_for_proxy' => false, 'can_vote' => true]);
    $college2 = College::factory()->create(['require_same_college_for_proxy' => false, 'can_vote' => true]);

    $collegeOrg1 = CollegeOrganization::factory()->create([
        'organization_id' => $organization->id,
        'college_id' => $college1->id,
    ]);
    $collegeOrg2 = CollegeOrganization::factory()->create([
        'organization_id' => $organization->id,
        'college_id' => $college2->id,
    ]);

    $seat1 = Seat::factory()->create(['college_organization_id' => $collegeOrg1->id]);
    $seat2 = Seat::factory()->create(['college_organization_id' => $collegeOrg2->id]);

    $validator = Validator::make(
        ['proxy_holder_seat_id' => $seat2->id],
        ['proxy_holder_seat_id' => [new ProxyDelegatesFromSameCollege($seat1->id)]]
    );

    expect($validator->passes())->toBeTrue();
});

test('validation rule fails for cross-college proxy when proxy holder college cannot vote', function () {
    $organization = Organization::factory()->create();
    $college1 = College::factory()->create(['require_same_college_for_proxy' => false, 'can_vote' => true]);
    $college2 = College::factory()->create(['require_same_college_for_proxy' => false, 'can_vote' => false]);

    $collegeOrg1 = CollegeOrganization::factory()->create([
        'organization_id' => $organization->id,
        'college_id' => $college1->id,
    ]);
    $collegeOrg2 = CollegeOrganization::factory()->create([
        'organization_id' => $organization->id,
        'college_id' => $college2->id,
    ]);

    $seat1 = Seat::factory()->create(['college_organization_id' => $collegeOrg1->id]);
    $seat2 = Seat::factory()->create(['college_organization_id' => $collegeOrg2->id]);

    $validator = Validator::make(
        ['proxy_holder_seat_id' => $seat2->id],
        ['proxy_holder_seat_id' => [new ProxyDelegatesFromSameCollege($seat1->id)]]
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('proxy_holder_seat_id'))
        ->toBe('Le siège mandataire doit appartenir à un collège délibérant.');
});

test('deleting session cascades to proxies', function () {
    $session = DemocraticTimeSession::factory()->create();
    $proxy = Proxy::factory()->create(['democratic_time_session_id' => $session->id]);
    $proxyId = $proxy->id;

    $session->delete();

    expect(Proxy::find($proxyId))->toBeNull();
});

test('deleting seat cascades to their given proxies', function () {
    $delegatorSeat = Seat::factory()->create();
    $proxy = Proxy::factory()->create(['delegator_seat_id' => $delegatorSeat->id]);
    $proxyId = $proxy->id;

    $delegatorSeat->forceDelete();

    expect(Proxy::find($proxyId))->toBeNull();
});

test('deleting seat cascades to their received proxies', function () {
    $proxyHolderSeat = Seat::factory()->create();
    $proxy = Proxy::factory()->create(['proxy_holder_seat_id' => $proxyHolderSeat->id]);
    $proxyId = $proxy->id;

    $proxyHolderSeat->forceDelete();

    expect(Proxy::find($proxyId))->toBeNull();
});

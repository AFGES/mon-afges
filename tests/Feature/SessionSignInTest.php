<?php

declare(strict_types=1);

use App\Models\College;
use App\Models\CollegeOrganization;
use App\Models\Delegate;
use App\Models\DemocraticTimeSession;
use App\Models\Organization;
use App\Models\Proxy;
use App\Models\Seat;
use App\Models\SessionSignIn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('session sign-in can be created with all required fields', function () {
    $session = DemocraticTimeSession::factory()->create();
    $seat = Seat::factory()->create();

    $signIn = SessionSignIn::factory()->create([
        'democratic_time_session_id' => $session->id,
        'seat_id' => $seat->id,
        'signed_in_at' => now(),
    ]);

    expect($signIn->democratic_time_session_id)->toBe($session->id)
        ->and($signIn->seat_id)->toBe($seat->id)
        ->and($signIn->signed_out_at)->toBeNull()
        ->and($signIn->isSignedIn())->toBeTrue();
});

test('delegate can sign in to a session', function () {
    $session = DemocraticTimeSession::factory()->create([
        'started_at' => now()->subHour(),
        'ended_at' => now()->addHour(),
    ]);
    $seat = Seat::factory()->create();
    $delegate = Delegate::factory()->approved()->create(['seat_id' => $seat->id]);

    $signIn = SessionSignIn::create([
        'democratic_time_session_id' => $session->id,
        'seat_id' => $seat->id,
        'delegate_id' => $delegate->id,
        'signed_in_at' => now(),
    ]);

    expect($signIn->isSignedIn())->toBeTrue()
        ->and($signIn->seat_id)->toBe($seat->id)
        ->and($signIn->delegate)->not->toBeNull()
        ->and($signIn->delegate->id)->toBe($delegate->id);
});

test('delegate can sign out from a session', function () {
    $session = DemocraticTimeSession::factory()->create([
        'started_at' => now()->subHour(),
        'ended_at' => now()->addHour(),
    ]);
    $seat = Seat::factory()->create();

    $signIn = SessionSignIn::factory()->create([
        'democratic_time_session_id' => $session->id,
        'seat_id' => $seat->id,
        'signed_in_at' => now()->subMinutes(30),
        'signed_out_at' => null,
    ]);

    expect($signIn->isSignedIn())->toBeTrue();

    $signIn->update(['signed_out_at' => now()]);

    expect($signIn->fresh()->isSignedIn())->toBeFalse()
        ->and($signIn->fresh()->signed_out_at)->not->toBeNull();
});

test('can detect if delegate signed out early', function () {
    $session = DemocraticTimeSession::factory()->create([
        'started_at' => now()->subHours(2),
        'ended_at' => now()->addHour(),
    ]);
    $seat = Seat::factory()->create();

    $signIn = SessionSignIn::factory()->create([
        'democratic_time_session_id' => $session->id,
        'seat_id' => $seat->id,
        'signed_in_at' => now()->subHour(),
        'signed_out_at' => now()->subMinutes(30),
    ]);

    expect($signIn->signedOutEarly())->toBeTrue();
});

test('delegate who stays until end did not sign out early', function () {
    $session = DemocraticTimeSession::factory()->create([
        'started_at' => now()->subHours(2),
        'ended_at' => now()->subMinutes(10),
    ]);
    $seat = Seat::factory()->create();

    $signIn = SessionSignIn::factory()->create([
        'democratic_time_session_id' => $session->id,
        'seat_id' => $seat->id,
        'signed_in_at' => now()->subHour(),
        'signed_out_at' => now()->subMinutes(5),
    ]);

    expect($signIn->signedOutEarly())->toBeFalse();
});

test('proxy holder can sign in on behalf of delegator', function () {
    $session = DemocraticTimeSession::factory()->create();
    $college = College::factory()->create();
    $organization = Organization::factory()->create();
    $collegeOrg = CollegeOrganization::factory()->create([
        'college_id' => $college->id,
        'organization_id' => $organization->id,
    ]);

    $delegatorSeat = Seat::factory()->create([
        'college_organization_id' => $collegeOrg->id,
        'seat_number' => 1,
    ]);
    $proxyHolderSeat = Seat::factory()->create([
        'college_organization_id' => $collegeOrg->id,
        'seat_number' => 2,
    ]);

    $delegator = Delegate::factory()->approved()->create(['seat_id' => $delegatorSeat->id]);

    $proxy = Proxy::factory()->approved()->create([
        'delegator_seat_id' => $delegatorSeat->id,
        'proxy_holder_seat_id' => $proxyHolderSeat->id,
        'democratic_time_session_id' => $session->id,
    ]);

    $signIn = SessionSignIn::create([
        'democratic_time_session_id' => $session->id,
        'seat_id' => $delegatorSeat->id,
        'delegate_id' => $delegator->id,
        'signed_in_by_seat_id' => $proxyHolderSeat->id,
        'signed_in_at' => now(),
    ]);

    expect($signIn->isProxySignIn())->toBeTrue()
        ->and($signIn->signedInBySeat->id)->toBe($proxyHolderSeat->id)
        ->and($signIn->seat->id)->toBe($delegatorSeat->id);
});

test('regular sign-in is not a proxy sign-in', function () {
    $session = DemocraticTimeSession::factory()->create();
    $seat = Seat::factory()->create();

    $signIn = SessionSignIn::factory()->create([
        'democratic_time_session_id' => $session->id,
        'seat_id' => $seat->id,
        'signed_in_by_seat_id' => null,
    ]);

    expect($signIn->isProxySignIn())->toBeFalse();
});

test('sign-in is unique per seat per delegate per session', function () {
    $session = DemocraticTimeSession::factory()->create();
    $seat = Seat::factory()->create();
    $delegate = Delegate::factory()->approved()->create(['seat_id' => $seat->id]);

    SessionSignIn::create([
        'democratic_time_session_id' => $session->id,
        'seat_id' => $seat->id,
        'delegate_id' => $delegate->id,
        'signed_in_at' => now(),
    ]);

    // Try to create duplicate for same delegate
    $this->expectException(\Illuminate\Database\QueryException::class);

    SessionSignIn::create([
        'democratic_time_session_id' => $session->id,
        'seat_id' => $seat->id,
        'delegate_id' => $delegate->id,
        'signed_in_at' => now(),
    ]);
});

test('multiple delegates from same seat can sign in to same session', function () {
    $session = DemocraticTimeSession::factory()->create();
    $seat = Seat::factory()->create();
    $delegate1 = Delegate::factory()->approved()->create(['seat_id' => $seat->id]);
    $delegate2 = Delegate::factory()->approved()->create(['seat_id' => $seat->id]);

    $signIn1 = SessionSignIn::create([
        'democratic_time_session_id' => $session->id,
        'seat_id' => $seat->id,
        'delegate_id' => $delegate1->id,
        'signed_in_at' => now(),
    ]);

    $signIn2 = SessionSignIn::create([
        'democratic_time_session_id' => $session->id,
        'seat_id' => $seat->id,
        'delegate_id' => $delegate2->id,
        'signed_in_at' => now(),
    ]);

    expect($signIn1->seat_id)->toBe($seat->id)
        ->and($signIn2->seat_id)->toBe($seat->id)
        ->and($signIn1->delegate_id)->toBe($delegate1->id)
        ->and($signIn2->delegate_id)->toBe($delegate2->id)
        ->and(SessionSignIn::where('seat_id', $seat->id)->count())->toBe(2);
});

test('deleting session cascades to sign-ins', function () {
    $session = DemocraticTimeSession::factory()->create();
    $seat = Seat::factory()->create();

    $signIn = SessionSignIn::factory()->create([
        'democratic_time_session_id' => $session->id,
        'seat_id' => $seat->id,
    ]);
    $signInId = $signIn->id;

    $session->delete();

    expect(SessionSignIn::find($signInId))->toBeNull();
});

test('deleting seat cascades to sign-ins', function () {
    $session = DemocraticTimeSession::factory()->create();
    $seat = Seat::factory()->create();

    $signIn = SessionSignIn::factory()->create([
        'democratic_time_session_id' => $session->id,
        'seat_id' => $seat->id,
    ]);
    $signInId = $signIn->id;

    $seat->forceDelete();

    expect(SessionSignIn::find($signInId))->toBeNull();
});

test('session sign-in can open manually', function () {
    $session = DemocraticTimeSession::factory()->create([
        'started_at' => now()->addHour(),
        'sign_in_opens_at' => null,
    ]);

    expect($session->sign_in_opens_at)->toBeNull();

    $result = $session->openSignIn();

    expect($result)->toBeTrue()
        ->and($session->fresh()->sign_in_opens_at)->not->toBeNull();
});

test('session sign-in cannot open if already opened', function () {
    $session = DemocraticTimeSession::factory()->create([
        'sign_in_opens_at' => now()->subMinutes(10),
    ]);

    $result = $session->openSignIn();

    expect($result)->toBeFalse();
});

test('session sign-in can close manually', function () {
    $session = DemocraticTimeSession::factory()->create([
        'sign_in_opens_at' => now()->subMinutes(10),
        'sign_in_closes_at' => null,
        'started_at' => now()->subHour(),
        'ended_at' => now()->addHour(),
    ]);
    $seat = Seat::factory()->create();

    // Create a delegate still signed in
    $signIn = SessionSignIn::factory()->create([
        'democratic_time_session_id' => $session->id,
        'seat_id' => $seat->id,
        'signed_in_at' => now()->subMinutes(30),
        'signed_out_at' => null,
    ]);

    expect($session->sign_in_closes_at)->toBeNull();

    $result = $session->closeSignIn();

    expect($result)->toBeTrue()
        ->and($session->fresh()->sign_in_closes_at)->not->toBeNull()
        ->and($signIn->fresh()->signed_out_at)->not->toBeNull()
        ->and($signIn->fresh()->signed_out_at->timestamp)->toBe($session->ended_at->timestamp);
});

test('session sign-in cannot close if already closed', function () {
    $session = DemocraticTimeSession::factory()->create([
        'sign_in_opens_at' => now()->subMinutes(30),
        'sign_in_closes_at' => now()->subMinutes(5),
    ]);

    $result = $session->closeSignIn();

    expect($result)->toBeFalse();
});

test('session sign-in detects if currently open', function () {
    $session = DemocraticTimeSession::factory()->create([
        'sign_in_opens_at' => now()->subMinutes(10),
        'sign_in_closes_at' => null,
    ]);

    expect($session->isSignInOpen())->toBeTrue();
});

test('session sign-in detects if not yet opened', function () {
    $session = DemocraticTimeSession::factory()->create([
        'sign_in_opens_at' => null,
        'sign_in_closes_at' => null,
    ]);

    expect($session->isSignInOpen())->toBeFalse();
});

test('session sign-in detects if closed', function () {
    $session = DemocraticTimeSession::factory()->create([
        'sign_in_opens_at' => now()->subHour(),
        'sign_in_closes_at' => now()->subMinutes(10),
    ]);

    expect($session->isSignInOpen())->toBeFalse();
});

test('session sign-in auto-opens based on minutes before start', function () {
    $session = DemocraticTimeSession::factory()->create([
        'started_at' => now()->addMinutes(10),
        'auto_open_sign_in' => true,
        'minutes_before_start' => 15,
        'sign_in_opens_at' => null,
    ]);

    $result = $session->autoOpenSignIn();

    expect($result)->toBeTrue()
        ->and($session->fresh()->sign_in_opens_at)->not->toBeNull();
});

test('session sign-in does not auto-open if time has not come', function () {
    $session = DemocraticTimeSession::factory()->create([
        'started_at' => now()->addMinutes(20),
        'auto_open_sign_in' => true,
        'minutes_before_start' => 15,
        'sign_in_opens_at' => null,
    ]);

    $result = $session->autoOpenSignIn();

    expect($result)->toBeFalse()
        ->and($session->fresh()->sign_in_opens_at)->toBeNull();
});

test('session sign-in does not auto-open if already opened', function () {
    $session = DemocraticTimeSession::factory()->create([
        'started_at' => now()->addMinutes(10),
        'auto_open_sign_in' => true,
        'minutes_before_start' => 15,
        'sign_in_opens_at' => now()->subMinutes(5),
    ]);

    $result = $session->autoOpenSignIn();

    expect($result)->toBeFalse();
});

test('session sign-in auto-closes when session ends', function () {
    $endTime = now()->subMinutes(10);
    $session = DemocraticTimeSession::factory()->create([
        'started_at' => now()->subHours(2),
        'ended_at' => $endTime,
        'sign_in_opens_at' => now()->subHours(2)->addMinutes(15),
        'sign_in_closes_at' => null,
    ]);
    $seat = Seat::factory()->create();

    // Create a delegate still signed in
    $signIn = SessionSignIn::factory()->create([
        'democratic_time_session_id' => $session->id,
        'seat_id' => $seat->id,
        'signed_in_at' => now()->subHour(),
        'signed_out_at' => null,
    ]);

    $result = $session->autoCloseSignIn();

    expect($result)->toBeTrue()
        ->and($session->fresh()->sign_in_closes_at)->not->toBeNull()
        ->and($session->fresh()->sign_in_closes_at->timestamp)->toBe($session->ended_at->timestamp)
        ->and($signIn->fresh()->signed_out_at)->not->toBeNull()
        ->and($signIn->fresh()->signed_out_at->timestamp)->toBe($endTime->timestamp);
});

test('session sign-in does not auto-close if already closed', function () {
    $session = DemocraticTimeSession::factory()->create([
        'started_at' => now()->subHours(2),
        'ended_at' => now()->subMinutes(10),
        'sign_in_opens_at' => now()->subHours(2)->addMinutes(15),
        'sign_in_closes_at' => now()->subMinutes(10),
    ]);

    $result = $session->autoCloseSignIn();

    expect($result)->toBeFalse();
});

test('can get delegate for sign-in directly from relationship', function () {
    $seat = Seat::factory()->create();
    $user = User::factory()->create();
    $session = DemocraticTimeSession::factory()->create([
        'started_at' => now()->subHour(),
        'ended_at' => now()->addHour(),
    ]);

    $delegate = Delegate::factory()->approved()->create([
        'user_id' => $user->id,
        'seat_id' => $seat->id,
        'started_at' => now()->subDays(10),
        'ended_at' => null,
    ]);

    $signIn = SessionSignIn::create([
        'democratic_time_session_id' => $session->id,
        'seat_id' => $seat->id,
        'delegate_id' => $delegate->id,
        'signed_in_at' => now(),
    ]);

    expect($signIn->delegate)->not->toBeNull()
        ->and($signIn->delegate->id)->toBe($delegate->id);
});

test('delegate relationship is required when creating sign-in', function () {
    $seat = Seat::factory()->create();
    $session = DemocraticTimeSession::factory()->create([
        'started_at' => now()->subHour(),
        'ended_at' => now()->addHour(),
    ]);

    // Try to create without delegate_id
    $this->expectException(\Illuminate\Database\QueryException::class);

    SessionSignIn::create([
        'democratic_time_session_id' => $session->id,
        'seat_id' => $seat->id,
        'signed_in_at' => now(),
    ]);
});

test('changing session end date syncs signed_out_at for matching sign-ins', function () {
    $oldEndTime = now()->subMinutes(10);
    $session = DemocraticTimeSession::factory()->create([
        'started_at' => now()->subHours(2),
        'ended_at' => $oldEndTime,
        'sign_in_opens_at' => now()->subHours(2)->addMinutes(15),
        'sign_in_closes_at' => $oldEndTime,
    ]);
    $seat = Seat::factory()->create();

    // Create a delegate who signed out at the session end time
    $signIn = SessionSignIn::factory()->create([
        'democratic_time_session_id' => $session->id,
        'seat_id' => $seat->id,
        'signed_in_at' => now()->subHour(),
        'signed_out_at' => $oldEndTime,
    ]);

    expect($signIn->signed_out_at->timestamp)->toBe($oldEndTime->timestamp);

    // Change the session end time
    $newEndTime = now()->addMinutes(10);
    $session->update(['ended_at' => $newEndTime]);

    // The sign-out time should be synced to the new end time
    expect($signIn->fresh()->signed_out_at->timestamp)->toBe($newEndTime->timestamp);
});

test('changing session end date does not affect manual sign-outs', function () {
    $sessionEndTime = now()->addHour();
    $manualSignOutTime = now()->subMinutes(30);

    $session = DemocraticTimeSession::factory()->create([
        'started_at' => now()->subHours(2),
        'ended_at' => $sessionEndTime,
        'sign_in_opens_at' => now()->subHours(2)->addMinutes(15),
        'sign_in_closes_at' => null,
    ]);
    $seat = Seat::factory()->create();

    // Create a delegate who signed out manually before session end
    $signIn = SessionSignIn::factory()->create([
        'democratic_time_session_id' => $session->id,
        'seat_id' => $seat->id,
        'signed_in_at' => now()->subHour(),
        'signed_out_at' => $manualSignOutTime,
    ]);

    expect($signIn->signed_out_at->timestamp)->toBe($manualSignOutTime->timestamp);

    // Change the session end time
    $newEndTime = now()->addHours(2);
    $session->update(['ended_at' => $newEndTime]);

    // The manual sign-out time should NOT be changed
    expect($signIn->fresh()->signed_out_at->timestamp)->toBe($manualSignOutTime->timestamp);
});

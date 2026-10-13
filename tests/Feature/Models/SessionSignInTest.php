<?php

declare(strict_types=1);

use App\Models\Delegate;
use App\Models\DemocraticTimeSession;
use App\Models\Seat;
use App\Models\SessionSignIn;
use Illuminate\Database\QueryException;

beforeEach(function (): void {
    $this->travelTo('2026-03-10 12:00:00');
});

describe('isSignedIn', function (): void {
    it('is true while the delegate has not signed out', function (): void {
        $signIn = SessionSignIn::factory()->create(['signed_out_at' => null]);

        expect($signIn->isSignedIn())->toBeTrue();
    });

    it('is false once the delegate has signed out', function (): void {
        $signIn = SessionSignIn::factory()->create(['signed_out_at' => '2026-03-10 11:30:00']);

        expect($signIn->isSignedIn())->toBeFalse();
    });
});

describe('signedOutEarly', function (): void {
    it('is true when the delegate signed out before the session ended', function (): void {
        $session = DemocraticTimeSession::factory()->create([
            'started_at' => '2026-03-10 10:00:00',
            'ended_at' => '2026-03-10 13:00:00',
        ]);
        $signIn = SessionSignIn::factory()->for($session)->create(['signed_out_at' => '2026-03-10 11:30:00']);

        expect($signIn->signedOutEarly())->toBeTrue();
    });

    it('is false when the delegate signed out after the session ended', function (): void {
        $session = DemocraticTimeSession::factory()->create([
            'started_at' => '2026-03-10 10:00:00',
            'ended_at' => '2026-03-10 11:50:00',
        ]);
        $signIn = SessionSignIn::factory()->for($session)->create(['signed_out_at' => '2026-03-10 11:55:00']);

        expect($signIn->signedOutEarly())->toBeFalse();
    });

    it('is false when the delegate signed out exactly when the session ended', function (): void {
        $session = DemocraticTimeSession::factory()->create([
            'started_at' => '2026-03-10 10:00:00',
            'ended_at' => '2026-03-10 11:50:00',
        ]);
        $signIn = SessionSignIn::factory()->for($session)->create(['signed_out_at' => '2026-03-10 11:50:00']);

        expect($signIn->signedOutEarly())->toBeFalse();
    });

    it('is false while the delegate is still signed in', function (): void {
        $signIn = SessionSignIn::factory()->create(['signed_out_at' => null]);

        expect($signIn->signedOutEarly())->toBeFalse();
    });

    it('is true when the delegate signed out of a session that has no end', function (): void {
        $session = DemocraticTimeSession::factory()->create(['ended_at' => null]);
        $signIn = SessionSignIn::factory()->for($session)->create(['signed_out_at' => '2026-03-10 11:30:00']);

        expect($signIn->signedOutEarly())->toBeTrue();
    });
});

describe('isProxySignIn', function (): void {
    it('is true when another seat signed in on behalf of the delegate', function (): void {
        $signIn = SessionSignIn::factory()->viaProxy()->create();

        expect($signIn->isProxySignIn())->toBeTrue();
    });

    it('is false when nobody signed in on behalf of the delegate', function (): void {
        $signIn = SessionSignIn::factory()->create(['signed_in_by_seat_id' => null]);

        expect($signIn->isProxySignIn())->toBeFalse();
    });

    it('is false when the delegate seat signed in for itself', function (): void {
        $signIn = SessionSignIn::factory()
            ->state(fn (array $attributes): array => ['signed_in_by_seat_id' => $attributes['seat_id']])
            ->create();

        expect($signIn->isProxySignIn())->toBeFalse();
    });
});

it('rejects a second sign-in of the same delegate to the same session', function (): void {
    $signIn = SessionSignIn::factory()->create();

    SessionSignIn::factory()->create([
        'democratic_time_session_id' => $signIn->democratic_time_session_id,
        'seat_id' => $signIn->seat_id,
        'delegate_id' => $signIn->delegate_id,
    ]);
})->throws(QueryException::class);

it('accepts sign-ins of several delegates of the same seat to the same session', function (): void {
    $session = DemocraticTimeSession::factory()->create();
    $seat = Seat::factory()->create();
    $firstDelegate = Delegate::factory()->approved()->for($seat)->create();
    $secondDelegate = Delegate::factory()->approved()->for($seat)->create();

    SessionSignIn::factory()->for($session)->for($seat)->for($firstDelegate)->create();
    SessionSignIn::factory()->for($session)->for($seat)->for($secondDelegate)->create();

    expect($seat->signIns()->count())->toBe(2);
});

it('requires a delegate', function (): void {
    SessionSignIn::factory()->create(['delegate_id' => null]);
})->throws(QueryException::class);

it('is deleted with its session', function (): void {
    $signIn = SessionSignIn::factory()->create();

    $signIn->democraticTimeSession->delete();

    $this->assertModelMissing($signIn);
});

it('is deleted with its seat', function (): void {
    $signIn = SessionSignIn::factory()->create();

    $signIn->seat->forceDelete();

    $this->assertModelMissing($signIn);
});

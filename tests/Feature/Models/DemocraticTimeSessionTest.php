<?php

declare(strict_types=1);

use App\Models\DemocraticTimeSession;
use App\Models\DemocraticTimeType;
use App\Models\SessionSignIn;

beforeEach(function (): void {
    $this->travelTo('2026-03-10 12:00:00');
});

it('casts the quorum settings read from the database', function (): void {
    $session = DemocraticTimeSession::factory()->create([
        'calculate_quorum_per_college' => 1,
        'quorum_percentage' => 60.5,
        'include_proxies_in_quorum' => 0,
    ]);

    $session->refresh();

    expect($session->calculate_quorum_per_college)->toBeTrue()
        ->and($session->quorum_percentage)->toBe('60.50')
        ->and($session->include_proxies_in_quorum)->toBeFalse();
});

it('falls back to the quorum settings of its type when it has none', function (bool $calculatePerCollege, string $expectedType): void {
    $type = DemocraticTimeType::factory()->create([
        'calculate_quorum_per_college' => $calculatePerCollege,
        'quorum_percentage' => 60.00,
        'include_proxies_in_quorum' => false,
    ]);
    $session = DemocraticTimeSession::factory()->for($type)->createQuietly([
        'calculate_quorum_per_college' => null,
        'quorum_percentage' => null,
        'include_proxies_in_quorum' => null,
    ]);

    $status = $session->getQuorumStatus();

    expect($status['type'])->toBe($expectedType);
})->with([
    'combined' => [false, 'combined'],
    'per college' => [true, 'per_college'],
]);

it('reports the type quorum percentage as required when it has none', function (): void {
    $type = DemocraticTimeType::factory()->create(['calculate_quorum_per_college' => false, 'quorum_percentage' => 60.00]);
    $session = DemocraticTimeSession::factory()->for($type)->createQuietly(['quorum_percentage' => null]);

    expect($session->getQuorumStatus()['required_percentage'])->toBe('60.00');
});

describe('isSignInOpen', function (): void {
    it('is closed when sign-in has not been opened', function (): void {
        $session = DemocraticTimeSession::factory()->create(['sign_in_opens_at' => null, 'sign_in_closes_at' => null]);

        expect($session->isSignInOpen())->toBeFalse();
    });

    it('is open once sign-in has opened and has no closing time', function (): void {
        $session = DemocraticTimeSession::factory()->create([
            'sign_in_opens_at' => '2026-03-10 11:50:00',
            'sign_in_closes_at' => null,
        ]);

        expect($session->isSignInOpen())->toBeTrue();
    });

    it('is open between its opening and closing times', function (): void {
        $session = DemocraticTimeSession::factory()->create([
            'sign_in_opens_at' => '2026-03-10 11:00:00',
            'sign_in_closes_at' => '2026-03-10 13:00:00',
        ]);

        expect($session->isSignInOpen())->toBeTrue();
    });

    it('is closed once its closing time has passed', function (): void {
        $session = DemocraticTimeSession::factory()->create([
            'sign_in_opens_at' => '2026-03-10 11:00:00',
            'sign_in_closes_at' => '2026-03-10 11:50:00',
        ]);

        expect($session->isSignInOpen())->toBeFalse();
    });

    it('is closed before its opening time', function (): void {
        $session = DemocraticTimeSession::factory()->create(['sign_in_opens_at' => '2026-03-10 12:30:00']);

        expect($session->isSignInOpen())->toBeFalse();
    });
});

describe('openSignIn', function (): void {
    it('opens sign-in now', function (): void {
        $session = DemocraticTimeSession::factory()->create(['sign_in_opens_at' => null]);

        $result = $session->openSignIn();

        expect($result)->toBeTrue()
            ->and($session->refresh()->sign_in_opens_at->toDateTimeString())->toBe('2026-03-10 12:00:00');
    });

    it('does not reopen sign-in that was already opened', function (): void {
        $session = DemocraticTimeSession::factory()->create(['sign_in_opens_at' => '2026-03-10 11:50:00']);

        $result = $session->openSignIn();

        expect($result)->toBeFalse()
            ->and($session->refresh()->sign_in_opens_at->toDateTimeString())->toBe('2026-03-10 11:50:00');
    });
});

describe('closeSignIn', function (): void {
    it('closes sign-in at the session end and signs out the delegates still signed in', function (): void {
        $session = DemocraticTimeSession::factory()->create([
            'started_at' => '2026-03-10 11:00:00',
            'ended_at' => '2026-03-10 13:00:00',
            'sign_in_closes_at' => null,
        ]);
        $presentSignIn = SessionSignIn::factory()->for($session)->create(['signed_out_at' => null]);
        $leftSignIn = SessionSignIn::factory()->for($session)->create(['signed_out_at' => '2026-03-10 11:30:00']);

        $result = $session->closeSignIn();

        expect($result)->toBeTrue()
            ->and($session->refresh()->sign_in_closes_at->toDateTimeString())->toBe('2026-03-10 13:00:00')
            ->and($presentSignIn->refresh()->signed_out_at->toDateTimeString())->toBe('2026-03-10 13:00:00')
            ->and($leftSignIn->refresh()->signed_out_at->toDateTimeString())->toBe('2026-03-10 11:30:00');
    });

    it('closes sign-in now when the session has no end', function (): void {
        $session = DemocraticTimeSession::factory()->create([
            'started_at' => '2026-03-10 11:00:00',
            'ended_at' => null,
            'sign_in_closes_at' => null,
        ]);
        $signIn = SessionSignIn::factory()->for($session)->create(['signed_out_at' => null]);

        $result = $session->closeSignIn();

        expect($result)->toBeTrue()
            ->and($session->refresh()->sign_in_closes_at->toDateTimeString())->toBe('2026-03-10 12:00:00')
            ->and($signIn->refresh()->signed_out_at->toDateTimeString())->toBe('2026-03-10 12:00:00');
    });

    it('does not close sign-in that was already closed', function (): void {
        $session = DemocraticTimeSession::factory()->create(['sign_in_closes_at' => '2026-03-10 11:55:00']);
        $signIn = SessionSignIn::factory()->for($session)->create(['signed_out_at' => null]);

        $result = $session->closeSignIn();

        expect($result)->toBeFalse()
            ->and($session->refresh()->sign_in_closes_at->toDateTimeString())->toBe('2026-03-10 11:55:00')
            ->and($signIn->refresh()->signed_out_at)->toBeNull();
    });
});

describe('autoOpenSignIn', function (): void {
    it('opens sign-in the configured minutes before the session starts', function (): void {
        $session = DemocraticTimeSession::factory()->create([
            'started_at' => '2026-03-10 12:10:00',
            'auto_open_sign_in' => true,
            'minutes_before_start' => 15,
            'sign_in_opens_at' => null,
        ]);

        $result = $session->autoOpenSignIn();

        expect($result)->toBeTrue()
            ->and($session->refresh()->sign_in_opens_at->toDateTimeString())->toBe('2026-03-10 11:55:00');
    });

    it('does not open sign-in before the configured time', function (): void {
        $session = DemocraticTimeSession::factory()->create([
            'started_at' => '2026-03-10 12:20:00',
            'auto_open_sign_in' => true,
            'minutes_before_start' => 15,
            'sign_in_opens_at' => null,
        ]);

        $result = $session->autoOpenSignIn();

        expect($result)->toBeFalse()
            ->and($session->refresh()->sign_in_opens_at)->toBeNull();
    });

    it('does not reopen sign-in that was already opened', function (): void {
        $session = DemocraticTimeSession::factory()->create([
            'started_at' => '2026-03-10 12:10:00',
            'auto_open_sign_in' => true,
            'minutes_before_start' => 15,
            'sign_in_opens_at' => '2026-03-10 11:56:00',
        ]);

        $result = $session->autoOpenSignIn();

        expect($result)->toBeFalse()
            ->and($session->refresh()->sign_in_opens_at->toDateTimeString())->toBe('2026-03-10 11:56:00');
    });

    it('does nothing when auto-opening is disabled', function (): void {
        $session = DemocraticTimeSession::factory()->create([
            'started_at' => '2026-03-10 12:10:00',
            'auto_open_sign_in' => false,
            'minutes_before_start' => 15,
            'sign_in_opens_at' => null,
        ]);

        $result = $session->autoOpenSignIn();

        expect($result)->toBeFalse()
            ->and($session->refresh()->sign_in_opens_at)->toBeNull();
    });

    it('does nothing when the session has no start', function (): void {
        $session = DemocraticTimeSession::factory()->make([
            'started_at' => null,
            'auto_open_sign_in' => true,
            'sign_in_opens_at' => null,
        ]);

        expect($session->autoOpenSignIn())->toBeFalse();
    });
});

describe('autoCloseSignIn', function (): void {
    it('closes sign-in at the session end and signs out the delegates still signed in', function (): void {
        $session = DemocraticTimeSession::factory()->create([
            'started_at' => '2026-03-10 10:00:00',
            'ended_at' => '2026-03-10 11:50:00',
            'sign_in_closes_at' => null,
        ]);
        $signIn = SessionSignIn::factory()->for($session)->create(['signed_out_at' => null]);

        $result = $session->autoCloseSignIn();

        expect($result)->toBeTrue()
            ->and($session->refresh()->sign_in_closes_at->toDateTimeString())->toBe('2026-03-10 11:50:00')
            ->and($signIn->refresh()->signed_out_at->toDateTimeString())->toBe('2026-03-10 11:50:00');
    });

    it('does not close sign-in that was already closed', function (): void {
        $session = DemocraticTimeSession::factory()->create([
            'started_at' => '2026-03-10 10:00:00',
            'ended_at' => '2026-03-10 11:50:00',
            'sign_in_closes_at' => '2026-03-10 11:45:00',
        ]);
        $signIn = SessionSignIn::factory()->for($session)->create(['signed_out_at' => null]);

        $result = $session->autoCloseSignIn();

        expect($result)->toBeFalse()
            ->and($session->refresh()->sign_in_closes_at->toDateTimeString())->toBe('2026-03-10 11:45:00')
            ->and($signIn->refresh()->signed_out_at)->toBeNull();
    });

    it('does nothing when the session has no end', function (): void {
        $session = DemocraticTimeSession::factory()->create(['ended_at' => null, 'sign_in_closes_at' => null]);
        $signIn = SessionSignIn::factory()->for($session)->create(['signed_out_at' => null]);

        $result = $session->autoCloseSignIn();

        expect($result)->toBeFalse()
            ->and($session->refresh()->sign_in_closes_at)->toBeNull()
            ->and($signIn->refresh()->signed_out_at)->toBeNull();
    });
});

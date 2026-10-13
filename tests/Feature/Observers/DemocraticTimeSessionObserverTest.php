<?php

declare(strict_types=1);

use App\Models\DemocraticTimeSession;
use App\Models\DemocraticTimeType;
use App\Models\SessionSignIn;

beforeEach(function (): void {
    $this->travelTo('2026-03-10 12:00:00');
});

describe('creating', function (): void {
    it('copies the quorum settings from its type', function (): void {
        $type = DemocraticTimeType::factory()->create([
            'calculate_quorum_per_college' => true,
            'quorum_percentage' => 60.00,
            'include_proxies_in_quorum' => false,
        ]);

        $session = DemocraticTimeSession::factory()->for($type)->create();

        expect($session->calculate_quorum_per_college)->toBeTrue()
            ->and($session->quorum_percentage)->toBe('60.00')
            ->and($session->include_proxies_in_quorum)->toBeFalse();
    });

    it('keeps the quorum settings given explicitly', function (): void {
        $type = DemocraticTimeType::factory()->create([
            'calculate_quorum_per_college' => true,
            'quorum_percentage' => 60.00,
            'include_proxies_in_quorum' => false,
        ]);

        $session = DemocraticTimeSession::factory()->for($type)->create([
            'calculate_quorum_per_college' => false,
            'quorum_percentage' => 75.00,
            'include_proxies_in_quorum' => true,
        ]);

        expect($session->calculate_quorum_per_college)->toBeFalse()
            ->and($session->quorum_percentage)->toBe('75.00')
            ->and($session->include_proxies_in_quorum)->toBeTrue();
    });
});

describe('updated', function (): void {
    it('moves the sign-outs made at the old end time to the new end time', function (): void {
        $session = DemocraticTimeSession::factory()->create([
            'started_at' => '2026-03-10 10:00:00',
            'ended_at' => '2026-03-10 11:50:00',
        ]);
        $signIn = SessionSignIn::factory()->for($session)->create(['signed_out_at' => '2026-03-10 11:50:00']);

        $session->update(['ended_at' => '2026-03-10 12:10:00']);

        expect($signIn->refresh()->signed_out_at->toDateTimeString())->toBe('2026-03-10 12:10:00');
    });

    it('keeps the sign-outs made manually before the end time', function (): void {
        $session = DemocraticTimeSession::factory()->create([
            'started_at' => '2026-03-10 10:00:00',
            'ended_at' => '2026-03-10 13:00:00',
        ]);
        $signIn = SessionSignIn::factory()->for($session)->create(['signed_out_at' => '2026-03-10 11:30:00']);

        $session->update(['ended_at' => '2026-03-10 14:00:00']);

        expect($signIn->refresh()->signed_out_at->toDateTimeString())->toBe('2026-03-10 11:30:00');
    });

    it('keeps the sign-outs when the session had no end time', function (): void {
        $session = DemocraticTimeSession::factory()->create([
            'started_at' => '2026-03-10 10:00:00',
            'ended_at' => null,
        ]);
        $signIn = SessionSignIn::factory()->for($session)->create(['signed_out_at' => '2026-03-10 11:30:00']);

        $session->update(['ended_at' => '2026-03-10 14:00:00']);

        expect($signIn->refresh()->signed_out_at->toDateTimeString())->toBe('2026-03-10 11:30:00');
    });

    it('keeps the sign-outs when the end time is removed', function (): void {
        $session = DemocraticTimeSession::factory()->create([
            'started_at' => '2026-03-10 10:00:00',
            'ended_at' => '2026-03-10 11:50:00',
        ]);
        $signIn = SessionSignIn::factory()->for($session)->create(['signed_out_at' => '2026-03-10 11:50:00']);

        $session->update(['ended_at' => null]);

        expect($signIn->refresh()->signed_out_at->toDateTimeString())->toBe('2026-03-10 11:50:00');
    });
});

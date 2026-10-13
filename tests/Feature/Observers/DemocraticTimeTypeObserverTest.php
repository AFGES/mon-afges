<?php

declare(strict_types=1);

use App\Models\DemocraticTimeSession;
use App\Models\DemocraticTimeType;

beforeEach(function (): void {
    $this->travelTo('2026-03-10 12:00:00');
});

function makeQuorumType(): DemocraticTimeType
{
    return DemocraticTimeType::factory()->create([
        'calculate_quorum_per_college' => true,
        'quorum_percentage' => 60.00,
        'include_proxies_in_quorum' => false,
    ]);
}

it('updates the quorum settings of future sessions', function (): void {
    $type = makeQuorumType();
    $futureSession = DemocraticTimeSession::factory()->for($type)->create([
        'started_at' => '2026-03-17 12:00:00',
        'ended_at' => '2026-03-17 14:00:00',
    ]);

    $type->update([
        'calculate_quorum_per_college' => false,
        'quorum_percentage' => 50.00,
        'include_proxies_in_quorum' => true,
    ]);

    $futureSession->refresh();
    expect($futureSession->calculate_quorum_per_college)->toBeFalse()
        ->and($futureSession->quorum_percentage)->toBe('50.00')
        ->and($futureSession->include_proxies_in_quorum)->toBeTrue();
});

it('updates every future session and leaves past sessions untouched', function (): void {
    $type = makeQuorumType();
    $nextWeekSession = DemocraticTimeSession::factory()->for($type)->create([
        'started_at' => '2026-03-17 12:00:00',
        'ended_at' => '2026-03-17 14:00:00',
    ]);
    $inTwoWeeksSession = DemocraticTimeSession::factory()->for($type)->create([
        'started_at' => '2026-03-24 12:00:00',
        'ended_at' => '2026-03-24 14:00:00',
    ]);
    $pastSession = DemocraticTimeSession::factory()->for($type)->create([
        'started_at' => '2026-03-03 12:00:00',
        'ended_at' => '2026-03-03 14:00:00',
    ]);

    $type->update(['quorum_percentage' => 50.00]);

    expect($nextWeekSession->refresh()->quorum_percentage)->toBe('50.00')
        ->and($inTwoWeeksSession->refresh()->quorum_percentage)->toBe('50.00')
        ->and($pastSession->refresh()->quorum_percentage)->toBe('60.00');
});

it('does not update sessions that are currently running', function (): void {
    $type = makeQuorumType();
    $activeSession = DemocraticTimeSession::factory()->for($type)->create([
        'started_at' => '2026-03-10 11:00:00',
        'ended_at' => '2026-03-10 13:00:00',
    ]);

    $type->update([
        'calculate_quorum_per_college' => false,
        'quorum_percentage' => 50.00,
        'include_proxies_in_quorum' => true,
    ]);

    $activeSession->refresh();
    expect($activeSession->calculate_quorum_per_college)->toBeTrue()
        ->and($activeSession->quorum_percentage)->toBe('60.00')
        ->and($activeSession->include_proxies_in_quorum)->toBeFalse();
});

it('only changes the quorum fields that were updated', function (): void {
    $type = makeQuorumType();
    $futureSession = DemocraticTimeSession::factory()->for($type)->create([
        'started_at' => '2026-03-17 12:00:00',
        'ended_at' => '2026-03-17 14:00:00',
        'quorum_percentage' => 55.00,
    ]);

    $type->update(['include_proxies_in_quorum' => true]);

    $futureSession->refresh();
    expect($futureSession->calculate_quorum_per_college)->toBeTrue()
        ->and($futureSession->quorum_percentage)->toBe('55.00')
        ->and($futureSession->include_proxies_in_quorum)->toBeTrue();
});

it('does not touch future sessions when a non-quorum field changes', function (): void {
    $type = makeQuorumType();
    $futureSession = DemocraticTimeSession::factory()->for($type)->create([
        'started_at' => '2026-03-17 12:00:00',
        'ended_at' => '2026-03-17 14:00:00',
    ]);
    $this->travel(1)->seconds();

    $type->update(['name' => 'New Name']);

    expect($futureSession->refresh()->updated_at->toDateTimeString())->toBe('2026-03-10 12:00:00');
});

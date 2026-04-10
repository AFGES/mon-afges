<?php

declare(strict_types=1);

use App\Models\DemocraticTimeSession;
use App\Models\DemocraticTimeType;

test('democratic time session can be created with all required fields', function () {
    $type = DemocraticTimeType::factory()->create();

    $session = DemocraticTimeSession::factory()->create([
        'democratic_time_type_id' => $type->id,
        'started_at' => '2026-02-15 14:00:00',
        'ended_at' => '2026-02-15 18:00:00',
        'location' => 'Salle de conférence A',
    ]);

    expect($session->democratic_time_type_id)->toBe($type->id)
        ->and($session->started_at->format('Y-m-d H:i:s'))->toBe('2026-02-15 14:00:00')
        ->and($session->ended_at->format('Y-m-d H:i:s'))->toBe('2026-02-15 18:00:00')
        ->and($session->location)->toBe('Salle de conférence A');
});

test('democratic time session belongs to democratic time type', function () {
    $type = DemocraticTimeType::factory()->create(['name' => 'Assemblée Générale']);
    $session = DemocraticTimeSession::factory()->create(['democratic_time_type_id' => $type->id]);

    expect($session->democraticTimeType->name)->toBe('Assemblée Générale');
});

test('democratic time session dates are cast to datetime', function () {
    $session = DemocraticTimeSession::factory()->create();

    expect($session->started_at)->toBeInstanceOf(Illuminate\Support\Carbon::class)
        ->and($session->ended_at)->toBeInstanceOf(Illuminate\Support\Carbon::class);
});

test('democratic time session can be updated', function () {
    $session = DemocraticTimeSession::factory()->create([
        'started_at' => '2026-02-15 14:00:00',
        'ended_at' => '2026-02-15 18:00:00',
        'location' => 'Salle A',
    ]);

    $session->update([
        'started_at' => '2026-02-16 10:00:00',
        'ended_at' => '2026-02-16 12:00:00',
        'location' => 'Salle B',
    ]);

    expect($session->fresh()->started_at->format('Y-m-d H:i:s'))->toBe('2026-02-16 10:00:00')
        ->and($session->fresh()->ended_at->format('Y-m-d H:i:s'))->toBe('2026-02-16 12:00:00')
        ->and($session->fresh()->location)->toBe('Salle B');
});

test('democratic time session can be deleted', function () {
    $session = DemocraticTimeSession::factory()->create();
    $id = $session->id;

    $session->delete();

    expect(DemocraticTimeSession::find($id))->toBeNull();
});

test('deleting democratic time type cascades to sessions', function () {
    $type = DemocraticTimeType::factory()->create();
    $session = DemocraticTimeSession::factory()->create(['democratic_time_type_id' => $type->id]);
    $sessionId = $session->id;

    $type->delete();

    expect(DemocraticTimeSession::find($sessionId))->toBeNull();
});

test('multiple sessions can exist for the same type', function () {
    $type = DemocraticTimeType::factory()->create();

    DemocraticTimeSession::factory()->create(['democratic_time_type_id' => $type->id]);
    DemocraticTimeSession::factory()->create(['democratic_time_type_id' => $type->id]);
    DemocraticTimeSession::factory()->create(['democratic_time_type_id' => $type->id]);

    expect(DemocraticTimeSession::where('democratic_time_type_id', $type->id)->count())->toBe(3);
});

test('democratic time session can check if currently active', function () {
    $activeSession = DemocraticTimeSession::factory()->create([
        'started_at' => now()->subDay(),
        'ended_at' => now()->addDay(),
    ]);

    $futureSession = DemocraticTimeSession::factory()->create([
        'started_at' => now()->addWeek(),
        'ended_at' => now()->addWeek()->addHours(2),
    ]);

    $pastSession = DemocraticTimeSession::factory()->create([
        'started_at' => now()->subWeek(),
        'ended_at' => now()->subWeek()->addHours(2),
    ]);

    expect($activeSession->isCurrentlyActive())->toBeTrue()
        ->and($futureSession->isCurrentlyActive())->toBeFalse()
        ->and($pastSession->isCurrentlyActive())->toBeFalse();
});

test('democratic time session can check if ended', function () {
    $activeSession = DemocraticTimeSession::factory()->create([
        'started_at' => now()->subDay(),
        'ended_at' => now()->addDay(),
    ]);

    $endedSession = DemocraticTimeSession::factory()->create([
        'started_at' => now()->subWeek(),
        'ended_at' => now()->subWeek()->addHours(2),
    ]);

    expect($activeSession->hasEnded())->toBeFalse()
        ->and($endedSession->hasEnded())->toBeTrue();
});

test('democratic time session can be terminated', function () {
    $session = DemocraticTimeSession::factory()->create([
        'started_at' => now()->subDay(),
        'ended_at' => now()->addDay(),
    ]);

    expect($session->isCurrentlyActive())->toBeTrue()
        ->and($session->ended_at->isFuture())->toBeTrue();

    $wasTerminated = $session->end();

    expect($wasTerminated)->toBeTrue()
        ->and($session->fresh()->ended_at)->not->toBeNull();
});

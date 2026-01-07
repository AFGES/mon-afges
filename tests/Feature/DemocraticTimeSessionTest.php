<?php

use App\Models\DemocraticTimeSession;
use App\Models\DemocraticTimeType;

test('democratic time session can be created with all required fields', function () {
    $type = DemocraticTimeType::factory()->create();

    $session = DemocraticTimeSession::factory()->create([
        'democratic_time_type_id' => $type->id,
        'starts_at' => '2026-02-15 14:00:00',
        'ends_at' => '2026-02-15 18:00:00',
        'location' => 'Salle de conférence A',
    ]);

    expect($session->democratic_time_type_id)->toBe($type->id)
        ->and($session->starts_at->format('Y-m-d H:i:s'))->toBe('2026-02-15 14:00:00')
        ->and($session->ends_at->format('Y-m-d H:i:s'))->toBe('2026-02-15 18:00:00')
        ->and($session->location)->toBe('Salle de conférence A');
});

test('democratic time session belongs to democratic time type', function () {
    $type = DemocraticTimeType::factory()->create(['name' => 'Assemblée Générale']);
    $session = DemocraticTimeSession::factory()->create(['democratic_time_type_id' => $type->id]);

    expect($session->democraticTimeType->name)->toBe('Assemblée Générale');
});

test('democratic time session dates are cast to datetime', function () {
    $session = DemocraticTimeSession::factory()->create();

    expect($session->starts_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($session->ends_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('democratic time session can be updated', function () {
    $session = DemocraticTimeSession::factory()->create([
        'starts_at' => '2026-02-15 14:00:00',
        'ends_at' => '2026-02-15 18:00:00',
        'location' => 'Salle A',
    ]);

    $session->update([
        'starts_at' => '2026-02-16 10:00:00',
        'ends_at' => '2026-02-16 12:00:00',
        'location' => 'Salle B',
    ]);

    expect($session->fresh()->starts_at->format('Y-m-d H:i:s'))->toBe('2026-02-16 10:00:00')
        ->and($session->fresh()->ends_at->format('Y-m-d H:i:s'))->toBe('2026-02-16 12:00:00')
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

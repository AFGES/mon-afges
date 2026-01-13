<?php

declare(strict_types=1);

use App\Models\DemocraticTimeSession;
use App\Models\DemocraticTimeType;

test('session copies quorum settings from type on creation', function () {
    $type = DemocraticTimeType::factory()->create([
        'calculate_quorum_per_college' => true,
        'quorum_percentage' => 60.00,
        'include_proxies_in_quorum' => false,
    ]);

    $session = DemocraticTimeSession::factory()->create([
        'democratic_time_type_id' => $type->id,
    ]);

    expect($session->calculate_quorum_per_college)->toBeTrue()
        ->and($session->quorum_percentage)->toEqual('60.00')
        ->and($session->include_proxies_in_quorum)->toBeFalse();
});

test('session quorum settings remain unchanged when type is modified', function () {
    $type = DemocraticTimeType::factory()->create([
        'calculate_quorum_per_college' => true,
        'quorum_percentage' => 60.00,
        'include_proxies_in_quorum' => false,
    ]);

    // Create a past session (already started)
    $session = DemocraticTimeSession::factory()->create([
        'democratic_time_type_id' => $type->id,
        'started_at' => now()->subWeek(),
        'ended_at' => now()->subWeek()->addHours(2),
    ]);

    // Verify initial values
    expect($session->calculate_quorum_per_college)->toBeTrue()
        ->and($session->quorum_percentage)->toEqual('60.00')
        ->and($session->include_proxies_in_quorum)->toBeFalse();

    // Modify the type
    $type->update([
        'calculate_quorum_per_college' => false,
        'quorum_percentage' => 50.00,
        'include_proxies_in_quorum' => true,
    ]);

    // Past session should retain original values
    $session->refresh();
    expect($session->calculate_quorum_per_college)->toBeTrue()
        ->and($session->quorum_percentage)->toEqual('60.00')
        ->and($session->include_proxies_in_quorum)->toBeFalse();
});

test('multiple sessions with same type preserve their own quorum settings', function () {
    $type = DemocraticTimeType::factory()->create([
        'calculate_quorum_per_college' => true,
        'quorum_percentage' => 60.00,
        'include_proxies_in_quorum' => false,
    ]);

    // Create a past session
    $session1 = DemocraticTimeSession::factory()->create([
        'democratic_time_type_id' => $type->id,
        'started_at' => now()->subWeek(),
        'ended_at' => now()->subWeek()->addHours(2),
    ]);

    // Change type settings before creating second session
    $type->update([
        'calculate_quorum_per_college' => false,
        'quorum_percentage' => 50.00,
        'include_proxies_in_quorum' => true,
    ]);

    // Create a future session
    $session2 = DemocraticTimeSession::factory()->create([
        'democratic_time_type_id' => $type->id,
        'started_at' => now()->addWeek(),
        'ended_at' => now()->addWeek()->addHours(2),
    ]);

    // First (past) session should keep original values
    expect($session1->fresh()->calculate_quorum_per_college)->toBeTrue()
        ->and($session1->fresh()->quorum_percentage)->toEqual('60.00')
        ->and($session1->fresh()->include_proxies_in_quorum)->toBeFalse();

    // Second (future) session should have new values
    expect($session2->calculate_quorum_per_college)->toBeFalse()
        ->and($session2->quorum_percentage)->toEqual('50.00')
        ->and($session2->include_proxies_in_quorum)->toBeTrue();
});

test('session can explicitly set quorum values overriding type defaults', function () {
    $type = DemocraticTimeType::factory()->create([
        'calculate_quorum_per_college' => true,
        'quorum_percentage' => 60.00,
        'include_proxies_in_quorum' => false,
    ]);

    $session = DemocraticTimeSession::factory()->create([
        'democratic_time_type_id' => $type->id,
        'calculate_quorum_per_college' => false,
        'quorum_percentage' => 75.00,
        'include_proxies_in_quorum' => true,
    ]);

    // Session should use explicitly set values, not type values
    expect($session->calculate_quorum_per_college)->toBeFalse()
        ->and($session->quorum_percentage)->toEqual('75.00')
        ->and($session->include_proxies_in_quorum)->toBeTrue();
});

test('session falls back to type values if quorum fields not set and accessed', function () {
    $type = DemocraticTimeType::factory()->create([
        'calculate_quorum_per_college' => false, // Use combined mode for simpler test
        'quorum_percentage' => 60.00,
        'include_proxies_in_quorum' => false,
    ]);

    // Manually insert session without quorum values to simulate old data
    $session = new DemocraticTimeSession([
        'democratic_time_type_id' => $type->id,
        'location' => 'Test Location',
        'started_at' => now(),
        'ended_at' => now()->addHours(2),
        'calculate_quorum_per_college' => null,
        'quorum_percentage' => null,
        'include_proxies_in_quorum' => null,
    ]);
    $session->saveQuietly(); // Skip observer

    $session->refresh();

    // Verify fields are null in database
    expect($session->getAttributes()['calculate_quorum_per_college'])->toBeNull()
        ->and($session->getAttributes()['quorum_percentage'])->toBeNull()
        ->and($session->getAttributes()['include_proxies_in_quorum'])->toBeNull();

    // Methods should still work by falling back to type
    $status = $session->getQuorumStatus();
    expect($status)->toBeArray()
        ->and($status['type'])->toBe('combined')
        ->and($status['required_percentage'])->toEqual(60.00);
});

test('session quorum fields are cast to correct types', function () {
    $session = DemocraticTimeSession::factory()->create([
        'calculate_quorum_per_college' => true,
        'quorum_percentage' => 60.50,
        'include_proxies_in_quorum' => false,
    ]);

    expect($session->calculate_quorum_per_college)->toBeBool()
        ->and($session->quorum_percentage)->toBeString() // Decimal cast returns string
        ->and($session->include_proxies_in_quorum)->toBeBool();
});

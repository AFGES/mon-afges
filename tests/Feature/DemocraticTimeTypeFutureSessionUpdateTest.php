<?php

declare(strict_types=1);

use App\Models\DemocraticTimeSession;
use App\Models\DemocraticTimeType;

test('updating type quorum fields updates future sessions', function () {
    $type = DemocraticTimeType::factory()->create([
        'calculate_quorum_per_college' => true,
        'quorum_percentage' => 60.00,
        'include_proxies_in_quorum' => false,
    ]);

    // Create a future session
    $futureSession = DemocraticTimeSession::factory()->create([
        'democratic_time_type_id' => $type->id,
        'started_at' => now()->addWeek(),
        'ended_at' => now()->addWeek()->addHours(2),
    ]);

    // Verify initial values
    expect($futureSession->calculate_quorum_per_college)->toBeTrue()
        ->and($futureSession->quorum_percentage)->toEqual('60.00')
        ->and($futureSession->include_proxies_in_quorum)->toBeFalse();

    // Update the type's quorum settings
    $type->update([
        'calculate_quorum_per_college' => false,
        'quorum_percentage' => 50.00,
        'include_proxies_in_quorum' => true,
    ]);

    // Future session should be updated
    $futureSession->refresh();
    expect($futureSession->calculate_quorum_per_college)->toBeFalse()
        ->and($futureSession->quorum_percentage)->toEqual('50.00')
        ->and($futureSession->include_proxies_in_quorum)->toBeTrue();
});

test('updating type quorum fields does not update past sessions', function () {
    $type = DemocraticTimeType::factory()->create([
        'calculate_quorum_per_college' => true,
        'quorum_percentage' => 60.00,
        'include_proxies_in_quorum' => false,
    ]);

    // Create a past session
    $pastSession = DemocraticTimeSession::factory()->create([
        'democratic_time_type_id' => $type->id,
        'started_at' => now()->subWeek(),
        'ended_at' => now()->subWeek()->addHours(2),
    ]);

    // Verify initial values
    expect($pastSession->calculate_quorum_per_college)->toBeTrue()
        ->and($pastSession->quorum_percentage)->toEqual('60.00')
        ->and($pastSession->include_proxies_in_quorum)->toBeFalse();

    // Update the type's quorum settings
    $type->update([
        'calculate_quorum_per_college' => false,
        'quorum_percentage' => 50.00,
        'include_proxies_in_quorum' => true,
    ]);

    // Past session should NOT be updated
    $pastSession->refresh();
    expect($pastSession->calculate_quorum_per_college)->toBeTrue()
        ->and($pastSession->quorum_percentage)->toEqual('60.00')
        ->and($pastSession->include_proxies_in_quorum)->toBeFalse();
});

test('updating type quorum fields does not update currently active sessions', function () {
    $type = DemocraticTimeType::factory()->create([
        'calculate_quorum_per_college' => true,
        'quorum_percentage' => 60.00,
        'include_proxies_in_quorum' => false,
    ]);

    // Create a currently active session (started in the past, ends in the future)
    $activeSession = DemocraticTimeSession::factory()->create([
        'democratic_time_type_id' => $type->id,
        'started_at' => now()->subHour(),
        'ended_at' => now()->addHour(),
    ]);

    // Verify initial values
    expect($activeSession->calculate_quorum_per_college)->toBeTrue()
        ->and($activeSession->quorum_percentage)->toEqual('60.00')
        ->and($activeSession->include_proxies_in_quorum)->toBeFalse();

    // Update the type's quorum settings
    $type->update([
        'calculate_quorum_per_college' => false,
        'quorum_percentage' => 50.00,
        'include_proxies_in_quorum' => true,
    ]);

    // Currently active session should NOT be updated (started_at is in the past)
    $activeSession->refresh();
    expect($activeSession->calculate_quorum_per_college)->toBeTrue()
        ->and($activeSession->quorum_percentage)->toEqual('60.00')
        ->and($activeSession->include_proxies_in_quorum)->toBeFalse();
});

test('updating type non-quorum fields does not trigger session updates', function () {
    $type = DemocraticTimeType::factory()->create([
        'name' => 'Original Name',
        'calculate_quorum_per_college' => true,
        'quorum_percentage' => 60.00,
        'include_proxies_in_quorum' => false,
    ]);

    // Create a future session
    $futureSession = DemocraticTimeSession::factory()->create([
        'democratic_time_type_id' => $type->id,
        'started_at' => now()->addWeek(),
        'ended_at' => now()->addWeek()->addHours(2),
    ]);

    $originalUpdatedAt = $futureSession->updated_at;

    // Wait a moment to ensure timestamp would change if updated
    sleep(1);

    // Update non-quorum field
    $type->update(['name' => 'New Name']);

    // Future session should NOT be updated
    $futureSession->refresh();
    expect($futureSession->updated_at->equalTo($originalUpdatedAt))->toBeTrue()
        ->and($futureSession->calculate_quorum_per_college)->toBeTrue()
        ->and($futureSession->quorum_percentage)->toEqual('60.00')
        ->and($futureSession->include_proxies_in_quorum)->toBeFalse();
});

test('updating only some quorum fields updates only those fields in future sessions', function () {
    $type = DemocraticTimeType::factory()->create([
        'calculate_quorum_per_college' => true,
        'quorum_percentage' => 60.00,
        'include_proxies_in_quorum' => false,
    ]);

    // Create a future session
    $futureSession = DemocraticTimeSession::factory()->create([
        'democratic_time_type_id' => $type->id,
        'started_at' => now()->addWeek(),
        'ended_at' => now()->addWeek()->addHours(2),
    ]);

    // Update only quorum_percentage
    $type->update(['quorum_percentage' => 75.00]);

    // Future session should have updated quorum_percentage, others unchanged
    $futureSession->refresh();
    expect($futureSession->calculate_quorum_per_college)->toBeTrue()
        ->and($futureSession->quorum_percentage)->toEqual('75.00')
        ->and($futureSession->include_proxies_in_quorum)->toBeFalse();
});

test('updating type updates multiple future sessions', function () {
    $type = DemocraticTimeType::factory()->create([
        'calculate_quorum_per_college' => true,
        'quorum_percentage' => 60.00,
        'include_proxies_in_quorum' => false,
    ]);

    // Create multiple future sessions
    $futureSession1 = DemocraticTimeSession::factory()->create([
        'democratic_time_type_id' => $type->id,
        'started_at' => now()->addWeek(),
        'ended_at' => now()->addWeek()->addHours(2),
    ]);

    $futureSession2 = DemocraticTimeSession::factory()->create([
        'democratic_time_type_id' => $type->id,
        'started_at' => now()->addWeeks(2),
        'ended_at' => now()->addWeeks(2)->addHours(2),
    ]);

    // Create a past session (should not be updated)
    $pastSession = DemocraticTimeSession::factory()->create([
        'democratic_time_type_id' => $type->id,
        'started_at' => now()->subWeek(),
        'ended_at' => now()->subWeek()->addHours(2),
    ]);

    // Update the type's quorum settings
    $type->update([
        'calculate_quorum_per_college' => false,
        'quorum_percentage' => 50.00,
        'include_proxies_in_quorum' => true,
    ]);

    // Both future sessions should be updated
    $futureSession1->refresh();
    expect($futureSession1->calculate_quorum_per_college)->toBeFalse()
        ->and($futureSession1->quorum_percentage)->toEqual('50.00')
        ->and($futureSession1->include_proxies_in_quorum)->toBeTrue();

    $futureSession2->refresh();
    expect($futureSession2->calculate_quorum_per_college)->toBeFalse()
        ->and($futureSession2->quorum_percentage)->toEqual('50.00')
        ->and($futureSession2->include_proxies_in_quorum)->toBeTrue();

    // Past session should remain unchanged
    $pastSession->refresh();
    expect($pastSession->calculate_quorum_per_college)->toBeTrue()
        ->and($pastSession->quorum_percentage)->toEqual('60.00')
        ->and($pastSession->include_proxies_in_quorum)->toBeFalse();
});

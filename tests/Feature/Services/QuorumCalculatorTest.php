<?php

declare(strict_types=1);

use App\Models\DemocraticTimeSession;
use App\Models\DemocraticTimeType;
use App\Services\QuorumCalculator;

it('does not meet quorum when no seat is eligible', function (): void {
    $type = DemocraticTimeType::factory()->create(['calculate_quorum_per_college' => false, 'quorum_percentage' => 0]);
    $session = DemocraticTimeSession::factory()->for($type)->createQuietly();

    $calculator = resolve(QuorumCalculator::class);

    expect($calculator->hasQuorum($session))->toBeFalse()
        ->and($calculator->status($session))->toMatchArray([
            'met' => false,
            'type' => 'combined',
            'total_eligible' => 0,
            'present_count' => 0,
        ]);
});

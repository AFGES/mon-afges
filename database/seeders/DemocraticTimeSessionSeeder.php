<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\DemocraticTimeSession;
use App\Models\DemocraticTimeType;
use Illuminate\Database\Seeder;

final class DemocraticTimeSessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = DemocraticTimeType::all();

        if ($types->isEmpty()) {
            return;
        }

        // Create some past sessions
        DemocraticTimeSession::factory(3)->create([
            'democratic_time_type_id' => $types->random()->id,
            'started_at' => now()->subMonths(2),
            'ended_at' => now()->subMonths(2)->addHours(3),
        ]);

        // Create current/upcoming sessions
        DemocraticTimeSession::factory(2)->create([
            'democratic_time_type_id' => $types->random()->id,
            'started_at' => now()->addWeeks(1),
            'ended_at' => now()->addWeeks(1)->addHours(3),
        ]);
    }
}

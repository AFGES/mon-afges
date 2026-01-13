<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\DemocraticTimeSession;
use App\Models\Proxy;
use App\Models\Seat;
use Illuminate\Database\Seeder;

final class ProxySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sessions = DemocraticTimeSession::all();
        $seats = Seat::inRandomOrder()->limit(10)->get();

        if ($sessions->isEmpty() || $seats->count() < 2) {
            return;
        }

        // Create some approved proxies with unique combinations
        for ($i = 0; $i < min(5, $seats->count() - 1); $i++) {
            Proxy::factory()->approved()->create([
                'delegator_seat_id' => $seats[$i]->id,
                'proxy_holder_seat_id' => $seats[$i + 1]->id,
                'democratic_time_session_id' => $sessions[$i % $sessions->count()]->id,
            ]);
        }

        // Create some pending proxies with unique combinations
        if ($seats->count() >= 4 && $sessions->count() >= 2) {
            Proxy::factory()->create([
                'delegator_seat_id' => $seats[0]->id,
                'proxy_holder_seat_id' => $seats[2]->id,
                'democratic_time_session_id' => $sessions[1]->id,
            ]);

            Proxy::factory()->create([
                'delegator_seat_id' => $seats[1]->id,
                'proxy_holder_seat_id' => $seats[3]->id,
                'democratic_time_session_id' => $sessions[0]->id,
            ]);
        }
    }
}

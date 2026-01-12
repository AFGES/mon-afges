<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Delegate;
use App\Models\DemocraticTimeSession;
use App\Models\SessionSignIn;
use Illuminate\Database\Seeder;

final class SessionSignInSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sessions = DemocraticTimeSession::where('started_at', '<=', now())->get();
        $delegates = Delegate::whereNotNull('seat_id')->limit(10)->get();

        if ($sessions->isEmpty() || $delegates->isEmpty()) {
            return;
        }

        // Create sign-ins for past sessions
        foreach ($sessions as $session) {
            foreach ($delegates->random(min(5, $delegates->count())) as $delegate) {
                SessionSignIn::firstOrCreate(
                    [
                        'democratic_time_session_id' => $session->id,
                        'seat_id' => $delegate->seat_id,
                        'delegate_id' => $delegate->id,
                    ],
                    [
                        'signed_in_at' => $session->started_at,
                        'signed_out_at' => fake()->optional(0.7)->dateTimeBetween(
                            $session->started_at,
                            $session->ended_at ?? now()
                        ),
                    ]
                );
            }
        }
    }
}

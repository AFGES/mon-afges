<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Delegate;
use App\Models\DemocraticTimeSession;
use App\Models\Seat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SessionSignIn>
 */
class SessionSignInFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $session = DemocraticTimeSession::factory()->create();
        $seat = Seat::factory()->create();
        $delegate = Delegate::factory()->approved()->for($seat)->create();
        $signedInAt = $session->started_at ?? now();

        return [
            'democratic_time_session_id' => $session->id,
            'seat_id' => $seat->id,
            'delegate_id' => $delegate->id,
            'signed_in_by_seat_id' => null,
            'signed_in_at' => $signedInAt,
            'signed_out_at' => null,
        ];
    }

    /**
     * Indicate that the sign-in was done via proxy.
     */
    public function viaProxy(): static
    {
        return $this->state(fn (array $attributes) => [
            'signed_in_by_seat_id' => Seat::factory(),
        ]);
    }

    /**
     * Indicate that the delegate has signed out.
     */
    public function signedOut(): static
    {
        return $this->state(function (array $attributes) {
            $signedInAt = $attributes['signed_in_at'] ?? now();

            return [
                'signed_out_at' => fake()->dateTimeBetween($signedInAt, '+2 hours'),
            ];
        });
    }
}

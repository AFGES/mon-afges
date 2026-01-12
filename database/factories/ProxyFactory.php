<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DemocraticTimeSession;
use App\Models\Seat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Proxy>
 */
class ProxyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delegator_seat_id' => Seat::factory(),
            'proxy_holder_seat_id' => Seat::factory(),
            'democratic_time_session_id' => DemocraticTimeSession::factory(),
            'started_at' => null,
            'ended_at' => null,
        ];
    }

    /**
     * Proxy is active from the beginning to the end (full session).
     */
    public function fullSession(): static
    {
        return $this->state(fn (array $attributes) => [
            'started_at' => null,
            'ended_at' => null,
        ]);
    }

    /**
     * Proxy starts from the beginning to a specific moment.
     */
    public function fromBeginning(): static
    {
        return $this->state(fn (array $attributes) => [
            'started_at' => null,
            'ended_at' => fake()->dateTimeBetween('now', '+2 hours'),
        ]);
    }

    /**
     * Proxy starts from a specific moment to the end.
     */
    public function toEnd(): static
    {
        return $this->state(fn (array $attributes) => [
            'started_at' => fake()->dateTimeBetween('now', '+1 hour'),
            'ended_at' => null,
        ]);
    }

    /**
     * Proxy is active between two specific moments.
     */
    public function betweenMoments(): static
    {
        $start = fake()->dateTimeBetween('now', '+1 hour');

        return $this->state(fn (array $attributes) => [
            'started_at' => $start,
            'ended_at' => fake()->dateTimeBetween($start, $start->format('Y-m-d H:i:s').' +2 hours'),
        ]);
    }
}

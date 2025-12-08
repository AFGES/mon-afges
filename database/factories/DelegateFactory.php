<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Delegate>
 */
class DelegateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'organization_id' => \App\Models\Organization::factory(),
            'seat_id' => \App\Models\Seat::factory(),
            'started_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'ended_at' => fake()->optional()->dateTimeBetween('now', '+1 year'),
        ];
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\College>
 */
class CollegeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['General', 'Federal', 'Advisory']),
            'can_vote' => fake()->boolean(75),
            'max_seats_per_organization' => fake()->optional(0.8)->numberBetween(1, 10),
            'max_delegates_per_seat' => fake()->optional(0.8)->numberBetween(1, 5),
        ];
    }

    public function general(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Général',
            'can_vote' => true,
        ]);
    }

    public function federal(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Fédéral',
            'can_vote' => true,
        ]);
    }

    public function advisory(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Consultatif',
            'can_vote' => false,
        ]);
    }
}

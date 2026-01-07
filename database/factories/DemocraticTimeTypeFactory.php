<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DemocraticTimeType>
 */
class DemocraticTimeTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'acronym' => strtoupper(fake()->lexify('???')),
            'convocation_interval_days' => fake()->numberBetween(1, 30),
        ];
    }
}

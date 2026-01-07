<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DemocraticTimeSession>
 */
class DemocraticTimeSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('now', '+1 month');
        $endsAt = fake()->dateTimeBetween($startsAt, $startsAt->format('Y-m-d H:i:s').' +4 hours');

        return [
            'democratic_time_type_id' => \App\Models\DemocraticTimeType::factory(),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'location' => fake()->address(),
        ];
    }
}

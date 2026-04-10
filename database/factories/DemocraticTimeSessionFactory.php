<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DemocraticTimeSession>
 */
final class DemocraticTimeSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('now', '+1 month');
        $endedAt = fake()->dateTimeBetween($startedAt, $startedAt->format('Y-m-d H:i:s').' +4 hours');

        return [
            'democratic_time_type_id' => \App\Models\DemocraticTimeType::factory(),
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'location' => fake()->address(),
        ];
    }
}

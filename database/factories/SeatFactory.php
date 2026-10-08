<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CollegeOrganization;
use App\Models\Seat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Seat>
 */
final class SeatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'college_organization_id' => CollegeOrganization::factory(),
            'seat_number' => fake()->unique()->numberBetween(1, 10),
        ];
    }
}

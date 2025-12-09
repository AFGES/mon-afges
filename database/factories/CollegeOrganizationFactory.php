<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CollegeOrganization>
 */
class CollegeOrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'college_id' => \App\Models\College::factory(),
            'organization_id' => \App\Models\Organization::factory(),
            'delegates_per_seat' => fake()->numberBetween(1, 3),
        ];
    }

    /**
     * Create a college organization with a specific number of seats.
     */
    public function withSeats(int $seats): static
    {
        return $this->afterCreating(function (\App\Models\CollegeOrganization $collegeOrganization) use ($seats) {
            // Create the specified number of seats
            for ($i = 1; $i <= $seats; $i++) {
                \App\Models\Seat::create([
                    'college_organization_id' => $collegeOrganization->id,
                    'seat_number' => $i,
                ]);
            }
        });
    }
}

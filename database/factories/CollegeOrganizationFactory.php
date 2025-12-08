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
            'seats' => fake()->numberBetween(1, 5),
            'delegates_per_seat' => fake()->numberBetween(1, 3),
        ];
    }
}

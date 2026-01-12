<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Volunteer>
 */
class VolunteerFactory extends Factory
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
            'position_title' => fake()->jobTitle(),
            'started_at' => now(),
            'ended_at' => null,
            'approval_status' => ApprovalStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => ApprovalStatus::Approved,
            'approved_at' => now(),
            'approved_by' => \App\Models\User::factory(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => ApprovalStatus::Rejected,
            'approved_at' => now(),
            'approved_by' => \App\Models\User::factory(),
            'rejection_reason' => fake()->sentence(),
        ]);
    }
}

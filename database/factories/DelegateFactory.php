<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Delegate>
 */
final class DelegateFactory extends Factory
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
            'email' => fake()->unique()->safeEmail(),
            'started_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'ended_at' => fake()->optional()->dateTimeBetween('now', '+1 year'),
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

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => now(),
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
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

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ApprovalStatus;
use App\Models\Seat;
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
            'seat_id' => Seat::factory(),
            'organization_id' => fn (array $attributes): string => Seat::query()
                ->findOrFail($attributes['seat_id'])
                ->collegeOrganization
                ->organization_id,
            'email' => fake()->unique()->safeEmail(),
            'started_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'ended_at' => fake()->optional()->dateTimeBetween('now', '+1 year'),
            'approval_status' => ApprovalStatus::Pending,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => ApprovalStatus::Pending,
        ]);
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

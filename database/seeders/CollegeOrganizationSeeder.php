<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\College;
use App\Models\CollegeOrganization;
use App\Models\Organization;
use Illuminate\Database\Seeder;

final class CollegeOrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $colleges = College::all();
        $organizations = Organization::all();

        // Create college-organization relationships with seats
        $colleges->each(function (College $college) use ($organizations) {
            // Each college gets 3-5 organizations
            $organizations->random(fake()->numberBetween(3, 5))->each(function (Organization $organization) use ($college) {
                // Create the college-organization relationship with random number of seats
                $seatCount = fake()->numberBetween(1, $college->max_seats_per_organization ?? 5);

                CollegeOrganization::factory()
                    ->withSeats($seatCount)
                    ->create([
                        'college_id' => $college->id,
                        'organization_id' => $organization->id,
                        'delegates_per_seat' => fake()->numberBetween(1, $college->max_delegates_per_seat ?? 3),
                    ]);
            });
        });
    }
}

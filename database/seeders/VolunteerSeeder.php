<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Volunteer;
use Illuminate\Database\Seeder;

final class VolunteerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some approved volunteers
        Volunteer::factory(5)->approved()->create();

        // Create some pending volunteers
        Volunteer::factory(3)->create();
    }
}

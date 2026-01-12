<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CollegeSeeder::class,
            OrganizationSeeder::class,
            CollegeOrganizationSeeder::class,
            SeatSeeder::class,
            DemocraticTimeTypeSeeder::class,
            DemocraticTimeSessionSeeder::class,
            DelegateSeeder::class,
            VolunteerSeeder::class,
            ProxySeeder::class,
            SessionSignInSeeder::class,
        ]);

        // Create a test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
    }
}

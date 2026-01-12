<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CollegeOrganization;
use App\Models\Seat;
use Illuminate\Database\Seeder;

final class SeatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create seats for each college-organization relationship
        CollegeOrganization::with('college')->get()->each(function (CollegeOrganization $collegeOrg) {
            $maxSeats = $collegeOrg->college->max_seats_per_organization ?? 6;

            for ($i = 1; $i <= $maxSeats; $i++) {
                Seat::firstOrCreate(
                    [
                        'college_organization_id' => $collegeOrg->id,
                        'seat_number' => $i,
                    ]
                );
            }
        });
    }
}

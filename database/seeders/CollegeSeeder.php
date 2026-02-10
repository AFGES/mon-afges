<?php

namespace Database\Seeders;

use App\Models\College;
use Illuminate\Database\Seeder;

class CollegeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $colleges = [
            ['name' => 'Général', 'can_vote' => true, 'max_seats_per_organization' => 6, 'max_delegates_per_seat' => 1, 'require_same_college_for_proxy' => true],
            ['name' => 'Fédéral', 'can_vote' => true, 'max_seats_per_organization' => 1, 'max_delegates_per_seat' => 2, 'require_same_college_for_proxy' => true],
            ['name' => 'Consultatif', 'can_vote' => false, 'max_seats_per_organization' => 1, 'max_delegates_per_seat' => 3, 'require_same_college_for_proxy' => true],
        ];

        College::upsert(
            $colleges,
            ['name'],
            ['can_vote', 'max_seats_per_organization', 'max_delegates_per_seat', 'require_same_college_for_proxy']
        );
    }
}

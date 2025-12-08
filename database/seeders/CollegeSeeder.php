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
            ['name' => 'Général', 'can_vote' => true],
            ['name' => 'Fédéral', 'can_vote' => true],
            ['name' => 'Consultatif', 'can_vote' => false],
        ];

        foreach ($colleges as $college) {
            College::firstOrCreate(
                ['name' => $college['name']],
                ['can_vote' => $college['can_vote']]
            );
        }
    }
}

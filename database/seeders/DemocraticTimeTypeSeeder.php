<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\DemocraticTimeType;
use Illuminate\Database\Seeder;

final class DemocraticTimeTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $democraticTimeTypes = [
            ['acronym' => 'AGO', 'name' => 'Assemblée Générale Ordinaire', 'convocation_interval_days' => 14],
            ['acronym' => 'AGE', 'name' => 'Assemblée Générale Extraordinaire', 'convocation_interval_days' => 14],
            ['acronym' => 'CA', 'name' => 'Conseil d\'Administration', 'convocation_interval_days' => 14],
            ['acronym' => 'CAD', 'name' => 'Conseil d\'Administration Dématérialisé', 'convocation_interval_days' => 7],
        ];

        foreach ($democraticTimeTypes as $type) {
            DemocraticTimeType::firstOrCreate($type);
        }
    }
}

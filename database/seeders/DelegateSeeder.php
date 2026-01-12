<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Delegate;
use App\Models\Organization;
use App\Models\Seat;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DelegateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some approved delegates with real users and seats
        $users = User::factory(10)->create();
        $seats = Seat::inRandomOrder()->limit(10)->get();
        $organizations = Organization::inRandomOrder()->limit(5)->get();

        foreach ($users as $index => $user) {
            if ($seats->has($index) && $organizations->has($index % 5)) {
                Delegate::factory()
                    ->approved()
                    ->verified()
                    ->create([
                        'user_id' => $user->id,
                        'seat_id' => $seats[$index]->id,
                        'organization_id' => $organizations[$index % 5]->id,
                        'email' => $user->email,
                    ]);
            }
        }

        // Create some pending delegates
        Delegate::factory(5)->create();
    }
}

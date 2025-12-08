<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CollegeOrganization;
use App\Models\Seat;
use Illuminate\Console\Command;

final class GenerateSeatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seats:generate {--force : Force regeneration of all seats}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate seats for all college-organization allocations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $force = $this->option('force');

        $collegeOrganizations = CollegeOrganization::query()
            ->with('seatRecords')
            ->get();

        $createdCount = 0;
        $skippedCount = 0;

        foreach ($collegeOrganizations as $collegeOrg) {
            $existingSeatsCount = $collegeOrg->seatRecords()->count();

            // Skip if seats already exist unless force is used
            if ($existingSeatsCount > 0 && ! $force) {
                $skippedCount++;

                continue;
            }

            // Delete existing seats if force is used
            if ($force && $existingSeatsCount > 0) {
                $collegeOrg->seatRecords()->delete();
            }

            // Generate seats based on the allocated number
            for ($i = 1; $i <= $collegeOrg->seats; $i++) {
                Seat::create([
                    'college_organization_id' => $collegeOrg->id,
                    'seat_number' => $i,
                ]);
                $createdCount++;
            }
        }

        $this->info("Created {$createdCount} seats.");

        if ($skippedCount > 0) {
            $this->info("Skipped {$skippedCount} college-organization allocations that already have seats.");
            $this->info('Use --force to regenerate all seats.');
        }

        return Command::SUCCESS;
    }
}

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
            // Note: This command now only works with college organizations that already have seats
            // It will fill in any missing seat numbers if some were soft deleted
            $allSeats = $collegeOrg->seatRecords()->withTrashed()->get();
            $activeSeats = $collegeOrg->seatRecords()->get();
            $activeSeatNumbers = $activeSeats->pluck('seat_number')->toArray();

            if ($allSeats->isEmpty()) {
                $this->warn("CollegeOrganization {$collegeOrg->id} has no seats. Create seats through the UI first.");
                $skippedCount++;

                continue;
            }

            // Get max seat number from all seats (including soft deleted)
            $maxSeatNumber = $allSeats->max('seat_number');

            // Delete existing seats if force is used
            if ($force) {
                $collegeOrg->seatRecords()->forceDelete();
                $activeSeatNumbers = [];
                $maxSeatNumber = 0;
            }

            // Fill in missing seat numbers (restore or create)
            for ($i = 1; $i <= $maxSeatNumber; $i++) {
                if (! in_array($i, $activeSeatNumbers)) {
                    // Check if a soft-deleted seat exists with this number
                    $softDeleted = $collegeOrg->seatRecords()
                        ->onlyTrashed()
                        ->where('seat_number', $i)
                        ->first();

                    if ($softDeleted) {
                        // Restore the soft-deleted seat
                        $softDeleted->restore();
                    } else {
                        // Create new seat
                        Seat::create([
                            'college_organization_id' => $collegeOrg->id,
                            'seat_number' => $i,
                        ]);
                    }
                    $createdCount++;
                }
            }

            if ($createdCount == 0) {
                $skippedCount++;
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

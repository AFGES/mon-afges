<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Organization extends Model
{
    /** @use HasFactory<\Database\Factories\OrganizationFactory> */
    use HasFactory;

    use SoftDeletes;

    public $fillable = [
        'short_name',
        'legal_name',
    ];

    public function colleges(): BelongsToMany
    {
        return $this->belongsToMany(College::class)
            ->using(CollegeOrganization::class)
            ->withPivot(['delegates_per_seat'])
            ->withTimestamps();
    }

    /**
     * Sync colleges with seat creation.
     */
    public function syncCollegesWithSeats(array $collegesData): void
    {
        foreach ($collegesData as $collegeId => $data) {
            // Attach or update the pivot
            $this->colleges()->syncWithoutDetaching([
                $collegeId => ['delegates_per_seat' => $data['delegates_per_seat']],
            ]);

            // Get the CollegeOrganization
            $collegeOrg = CollegeOrganization::where('college_id', $collegeId)
                ->where('organization_id', $this->id)
                ->first();

            if ($collegeOrg && isset($data['seats'])) {
                $desiredSeats = $data['seats'];
                $currentSeats = $collegeOrg->seatRecords()->count();

                if ($desiredSeats > $currentSeats) {
                    // Add more seats
                    $existingSeatNumbers = $collegeOrg->seatRecords()->pluck('seat_number')->toArray();
                    $nextSeatNumber = empty($existingSeatNumbers) ? 1 : max($existingSeatNumbers) + 1;

                    for ($i = 0; $i < ($desiredSeats - $currentSeats); $i++) {
                        Seat::create([
                            'college_organization_id' => $collegeOrg->id,
                            'seat_number' => $nextSeatNumber + $i,
                        ]);
                    }
                } elseif ($desiredSeats < $currentSeats) {
                    // Remove empty seats only
                    $seatsToRemove = $currentSeats - $desiredSeats;
                    $emptySeats = $collegeOrg->seatRecords()
                        ->whereDoesntHave('delegates')
                        ->orderBy('seat_number', 'desc')
                        ->limit($seatsToRemove)
                        ->get();

                    if ($emptySeats->count() < $seatsToRemove) {
                        throw new \Exception("Cannot remove {$seatsToRemove} seats. Only {$emptySeats->count()} empty seats available.");
                    }

                    foreach ($emptySeats as $seat) {
                        $seat->delete();
                    }
                }
            }
        }

        // Detach colleges that are no longer in the data
        $collegeIds = array_keys($collegesData);
        $this->colleges()->sync($collegeIds);
    }
}

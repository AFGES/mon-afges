<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CollegeOrganization;
use App\Models\Organization;
use App\Models\Seat;
use Exception;

final class OrganizationCollegeSyncService
{
    /**
     * Sync colleges with seat creation/deletion.
     *
     * @param  array<string, array{delegates_per_seat: int, seats?: int}>  $collegesData
     *
     * @throws Exception
     */
    public function sync(Organization $organization, array $collegesData): void
    {
        foreach ($collegesData as $collegeId => $data) {
            $this->syncCollege($organization, (string) $collegeId, $data);
        }

        // Detach colleges that are no longer in the data
        $collegeIds = array_keys($collegesData);
        $organization->colleges()->sync($collegeIds);
    }

    /**
     * Sync a single college with its seats.
     *
     * @param  array{delegates_per_seat: int, seats?: int}  $data
     *
     * @throws Exception
     */
    private function syncCollege(Organization $organization, string $collegeId, array $data): void
    {
        // Attach or update the pivot
        $organization->colleges()->syncWithoutDetaching([
            $collegeId => ['delegates_per_seat' => $data['delegates_per_seat']],
        ]);

        if (! isset($data['seats'])) {
            return;
        }

        $collegeOrg = CollegeOrganization::where('college_id', $collegeId)
            ->where('organization_id', $organization->id)
            ->first();

        if (! $collegeOrg) {
            return;
        }

        $this->adjustSeats($collegeOrg, $data['seats']);
    }

    /**
     * Adjust the number of seats for a college organization.
     *
     * @throws Exception
     */
    private function adjustSeats(CollegeOrganization $collegeOrg, int $desiredSeats): void
    {
        $currentSeats = $collegeOrg->seatRecords()->count();

        if ($desiredSeats > $currentSeats) {
            $this->addSeats($collegeOrg, $desiredSeats - $currentSeats);
        } elseif ($desiredSeats < $currentSeats) {
            $this->removeSeats($collegeOrg, $currentSeats - $desiredSeats);
        }
    }

    /**
     * Add seats to a college organization.
     */
    private function addSeats(CollegeOrganization $collegeOrg, int $count): void
    {
        $existingSeatNumbers = $collegeOrg->seatRecords()->pluck('seat_number')->toArray();
        $nextSeatNumber = empty($existingSeatNumbers) ? 1 : max($existingSeatNumbers) + 1;

        for ($i = 0; $i < $count; $i++) {
            Seat::create([
                'college_organization_id' => $collegeOrg->id,
                'seat_number' => $nextSeatNumber + $i,
            ]);
        }
    }

    /**
     * Remove empty seats from a college organization.
     *
     * @throws Exception
     */
    private function removeSeats(CollegeOrganization $collegeOrg, int $count): void
    {
        $emptySeats = $collegeOrg->seatRecords()
            ->whereDoesntHave('delegates')
            ->orderBy('seat_number', 'desc')
            ->limit($count)
            ->get();

        if ($emptySeats->count() < $count) {
            $collegeName = $collegeOrg->college->name;
            $message = __('organization.notifications.cannot_remove_seats.body', [
                'college' => $collegeName,
                'empty' => $emptySeats->count(),
                'requested' => $count,
            ]);

            throw new Exception($message);
        }

        foreach ($emptySeats as $seat) {
            $seat->delete();
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SeatFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Seat extends Model
{
    /** @use HasFactory<SeatFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'college_organization_id',
        'seat_number',
    ];

    /** @return BelongsTo<CollegeOrganization, $this> */
    public function collegeOrganization(): BelongsTo
    {
        return $this->belongsTo(CollegeOrganization::class);
    }

    /** @return HasMany<Delegate, $this> */
    public function delegates(): HasMany
    {
        return $this->hasMany(Delegate::class);
    }

    /**
     * Proxies where this seat's holder is giving their proxy to another seat.
     *
     * @return HasMany<Proxy, $this>
     */
    public function proxiesGiven(): HasMany
    {
        return $this->hasMany(Proxy::class, 'delegator_seat_id');
    }

    /**
     * Proxies where this seat's holder is receiving proxy from another seat.
     *
     * @return HasMany<Proxy, $this>
     */
    public function proxiesReceived(): HasMany
    {
        return $this->hasMany(Proxy::class, 'proxy_holder_seat_id');
    }

    /** @return HasMany<SessionSignIn, $this> */
    public function signIns(): HasMany
    {
        return $this->hasMany(SessionSignIn::class);
    }

    /**
     * Check if this seat belongs to the same college as another seat.
     */
    public function isSameCollegeAs(self $otherSeat): bool
    {
        $thisCollegeId = $this->collegeOrganization?->college_id;
        $otherCollegeId = $otherSeat->collegeOrganization?->college_id;

        if ($thisCollegeId === null || $otherCollegeId === null) {
            return false;
        }

        return $thisCollegeId === $otherCollegeId;
    }

    /**
     * @return HasOneThrough<College, CollegeOrganization, $this>
     */
    public function college(): HasOneThrough
    {
        return $this->hasOneThrough(
            College::class,
            CollegeOrganization::class,
            firstKey: 'id',
            secondKey: 'id',
            localKey: 'college_organization_id',
            secondLocalKey: 'college_id',
        );
    }

    /**
     * @return HasOneThrough<Organization, CollegeOrganization, $this>
     */
    public function organization(): HasOneThrough
    {
        return $this->hasOneThrough(
            Organization::class,
            CollegeOrganization::class,
            firstKey: 'id',
            secondKey: 'id',
            localKey: 'college_organization_id',
            secondLocalKey: 'organization_id',
        );
    }

    /**
     * Check if the seat is empty (has no delegates).
     */
    public function isEmpty(): bool
    {
        return $this->delegates()->count() === 0;
    }

    /**
     * Get the label for the seat.
     * Format: "College Name: Delegate1, Delegate2, ..." ("Vide" when there are no delegates).
     */
    public function label(): string
    {
        $college = $this->collegeOrganization->college;
        $delegates = $this->delegates()->with('user')->get();

        $delegateNames = $delegates->map(fn (Delegate $delegate): string => $delegate->user->name)->join(', ');

        return sprintf(
            '%s: %s',
            $college->name,
            $delegateNames ?: 'Vide'
        );
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'seat_number' => 'integer',
        ];
    }
}

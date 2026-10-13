<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CollegeOrganizationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

final class CollegeOrganization extends Pivot
{
    /** @use HasFactory<CollegeOrganizationFactory> */
    use HasFactory;

    use HasUuids;

    public $incrementing = false;

    protected $table = 'college_organization';

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'college_id',
        'organization_id',
        'delegates_per_seat',
    ];

    /**
     * Get the number of seats (count of seat records).
     */
    public function getSeatsAttribute(): int
    {
        return $this->seatRecords()->count();
    }

    /** @return BelongsTo<College, $this> */
    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return HasMany<Seat, $this> */
    public function seatRecords(): HasMany
    {
        return $this->hasMany(Seat::class, 'college_organization_id', 'id');
    }

    /**
     * Calculate total delegate positions available.
     */
    public function getTotalDelegatePositionsAttribute(): int
    {
        return $this->seats * $this->delegates_per_seat;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'delegates_per_seat' => 'integer',
        ];
    }
}

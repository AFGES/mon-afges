<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

final class CollegeOrganization extends Pivot
{
    /** @use HasFactory<\Database\Factories\CollegeOrganizationFactory> */
    use HasFactory;

    protected $table = 'college_organization';

    public $incrementing = true;

    protected $fillable = [
        'college_id',
        'organization_id',
        'delegates_per_seat',
    ];

    protected function casts(): array
    {
        return [
            'delegates_per_seat' => 'integer',
        ];
    }

    /**
     * Get the number of seats (count of seat records).
     */
    public function getSeatsAttribute(): int
    {
        return $this->seatRecords()->count();
    }

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function seatRecords(): HasMany
    {
        return $this->hasMany(Seat::class, 'college_organization_id', 'id');
    }

    /**
     * Calculate total delegate positions available
     */
    public function getTotalDelegatePositionsAttribute(): int
    {
        return $this->seats * $this->delegates_per_seat;
    }
}

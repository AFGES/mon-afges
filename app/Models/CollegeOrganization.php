<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

final class CollegeOrganization extends Pivot
{
    protected $table = 'college_organization';

    protected $fillable = [
        'seats',
        'delegates_per_seat',
    ];

    protected function casts(): array
    {
        return [
            'seats' => 'integer',
            'delegates_per_seat' => 'integer',
        ];
    }

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Calculate total delegate positions available
     */
    public function getTotalDelegatePositionsAttribute(): int
    {
        return $this->seats * $this->delegates_per_seat;
    }
}

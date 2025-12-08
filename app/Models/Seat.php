<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Seat extends Model
{
    /** @use HasFactory<\Database\Factories\SeatFactory> */
    use HasFactory;

    protected $fillable = [
        'college_organization_id',
        'seat_number',
    ];

    protected function casts(): array
    {
        return [
            'seat_number' => 'integer',
        ];
    }

    public function collegeOrganization(): BelongsTo
    {
        return $this->belongsTo(CollegeOrganization::class);
    }

    public function delegates(): HasMany
    {
        return $this->hasMany(Delegate::class);
    }

    public function college(): BelongsTo
    {
        return $this->collegeOrganization->college();
    }

    public function organization(): BelongsTo
    {
        return $this->collegeOrganization->organization();
    }
}

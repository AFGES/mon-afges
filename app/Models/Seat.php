<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Seat extends Model
{
    /** @use HasFactory<\Database\Factories\SeatFactory> */
    use HasFactory;

    use SoftDeletes;

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

    /**
     * Check if the seat is empty (has no delegates).
     */
    public function isEmpty(): bool
    {
        return $this->delegates()->count() === 0;
    }

    /**
     * Get the label for the seat.
     * Format: "Short Name Org (College Name): Delegate1, Delegate2, ..."
     */
    public function label(): string
    {
        // $organization = $this->collegeOrganization->organization;
        $college = $this->collegeOrganization->college;
        $delegates = $this->delegates()->with('user')->get();

        $delegateNames = $delegates->map(fn ($delegate) => $delegate->user->name)->join(', ');

        // return sprintf(
        //     '%s (%s): %s',
        //     $organization->short_name,
        //     $college->name,
        //     $delegateNames ?: 'Vide'
        // );
        return sprintf(
            '%s: %s',
            $college->name,
            $delegateNames ?: 'Vide'
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Organization extends Model
{
    /** @use HasFactory<\Database\Factories\OrganizationFactory> */
    use HasFactory;

    use SoftDeletes;

    public $fillable = [
        'short_name',
        'legal_name',
        'domain',
    ];

    public function colleges(): BelongsToMany
    {
        return $this->belongsToMany(College::class)
            ->using(CollegeOrganization::class)
            ->withPivot(['delegates_per_seat'])
            ->withTimestamps();
    }

    public function delegates(): HasMany
    {
        return $this->hasMany(Delegate::class);
    }

    public function volunteers(): HasMany
    {
        return $this->hasMany(Volunteer::class);
    }

    /**
     * Sync colleges with seat creation.
     */
    public function syncCollegesWithSeats(array $collegesData): void
    {
        app(\App\Services\OrganizationCollegeSyncService::class)->sync($this, $collegesData);
    }
}

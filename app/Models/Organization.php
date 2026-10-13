<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\OrganizationCollegeSyncService;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'short_name',
        'legal_name',
        'domain',
    ];

    /** @return BelongsToMany<College, $this, CollegeOrganization> */
    public function colleges(): BelongsToMany
    {
        return $this->belongsToMany(College::class)
            ->using(CollegeOrganization::class)
            ->withPivot(['delegates_per_seat'])
            ->withTimestamps();
    }

    /** @return HasMany<Delegate, $this> */
    public function delegates(): HasMany
    {
        return $this->hasMany(Delegate::class);
    }

    /** @return HasMany<Volunteer, $this> */
    public function volunteers(): HasMany
    {
        return $this->hasMany(Volunteer::class);
    }

    /**
     * Sync colleges with seat creation.
     *
     * @param  array<string, array{delegates_per_seat: int, seats?: int}>  $collegesData
     */
    public function syncCollegesWithSeats(array $collegesData): void
    {
        app(OrganizationCollegeSyncService::class)->sync($this, $collegesData);
    }
}

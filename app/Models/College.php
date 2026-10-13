<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CollegeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

final class College extends Model
{
    /** @use HasFactory<CollegeFactory> */
    use HasFactory;

    use HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'can_vote',
        'max_seats_per_organization',
        'max_delegates_per_seat',
        'require_same_college_for_proxy',
    ];

    /**
     * @return HasManyThrough<Seat, CollegeOrganization, $this>
     */
    public function seats(): HasManyThrough
    {
        return $this->hasManyThrough(
            Seat::class,
            CollegeOrganization::class,
            firstKey: 'college_id',
            secondKey: 'college_organization_id',
        );
    }

    /**
     * Query the delegates sitting on seats of this college (two hops away, so not a native relation).
     *
     * @return Builder<Delegate>
     */
    public function delegates(): Builder
    {
        return Delegate::query()->whereIn(
            'seat_id',
            $this->seats()->select('seats.id')->getQuery()
        );
    }

    /** @return BelongsToMany<Organization, $this, CollegeOrganization> */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->using(CollegeOrganization::class)
            ->withPivot(['seats', 'delegates_per_seat'])
            ->withTimestamps();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'can_vote' => 'boolean',
            'max_seats_per_organization' => 'integer',
            'max_delegates_per_seat' => 'integer',
            'require_same_college_for_proxy' => 'boolean',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

final class College extends Model
{
    /** @use HasFactory<\Database\Factories\CollegeFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'can_vote',
        'max_seats_per_organization',
        'max_delegates_per_seat',
        'require_same_college_for_proxy',
    ];

    public function delegates(): HasManyThrough
    {
        return $this->hasManyThrough(
            Delegate::class,
            Seat::class,
            firstKey: 'college_organization_id', // This won't work as expected
            secondKey: 'seat_id',
            localKey: 'id',
            secondLocalKey: 'id'
        )
            ->join('college_organization', 'seats.college_organization_id', '=', 'college_organization.id')
            ->where('college_organization.college_id', $this->getKey())
            ->select('delegates.*');
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->using(CollegeOrganization::class)
            ->withPivot(['seats', 'delegates_per_seat'])
            ->withTimestamps();
    }

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

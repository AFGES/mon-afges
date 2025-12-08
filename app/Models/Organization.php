<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Organization extends Model
{
    /** @use HasFactory<\Database\Factories\OrganizationFactory> */
    use HasFactory;

    use SoftDeletes;

    public $fillable = [
        'short_name',
        'legal_name',
    ];

    public function colleges(): BelongsToMany
    {
        return $this->belongsToMany(College::class)
            ->using(CollegeOrganization::class)
            ->withPivot(['seats', 'delegates_per_seat'])
            ->withTimestamps();
    }
}

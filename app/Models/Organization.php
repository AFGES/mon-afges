<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

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
            ->withPivot(['id', 'delegates_per_seat'])
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
     * The email domain is stored lowercase without a leading at sign so logins can match it exactly.
     *
     * @return Attribute<never, ?string>
     */
    protected function domain(): Attribute
    {
        return Attribute::set(function (?string $value): ?string {
            $domain = Str::of((string) $value)->trim()->ltrim('@')->lower()->toString();

            return $domain === '' ? null : $domain;
        });
    }
}

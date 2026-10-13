<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DemocraticTimeTypeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DemocraticTimeType extends Model
{
    /** @use HasFactory<DemocraticTimeTypeFactory> */
    use HasFactory;

    use HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'acronym',
        'convocation_interval_days',
        'calculate_quorum_per_college',
        'quorum_percentage',
        'include_proxies_in_quorum',
    ];

    /** @return HasMany<DemocraticTimeSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(DemocraticTimeSession::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'convocation_interval_days' => 'integer',
            'calculate_quorum_per_college' => 'boolean',
            'quorum_percentage' => 'decimal:2',
            'include_proxies_in_quorum' => 'boolean',
        ];
    }
}

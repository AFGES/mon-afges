<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DemocraticTimeType extends Model
{
    /** @use HasFactory<\Database\Factories\DemocraticTimeTypeFactory> */
    use HasFactory;

    public $fillable = [
        'name',
        'acronym',
        'convocation_interval_days',
    ];

    protected function casts(): array
    {
        return [
            'convocation_interval_days' => 'integer',
        ];
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(DemocraticTimeSession::class);
    }
}

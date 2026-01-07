<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DemocraticTimeSession extends Model
{
    /** @use HasFactory<\Database\Factories\DemocraticTimeSessionFactory> */
    use HasFactory;

    public $fillable = [
        'democratic_time_type_id',
        'starts_at',
        'ends_at',
        'location',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(DemocraticTimeType::class);
    }
}

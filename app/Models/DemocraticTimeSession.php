<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DemocraticTimeSession extends Model
{
    /** @use HasFactory<\Database\Factories\DemocraticTimeSessionFactory> */
    use HasFactory;

    use HasPeriod;

    public $fillable = [
        'democratic_time_type_id',
        'location',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(DemocraticTimeType::class);
    }
}

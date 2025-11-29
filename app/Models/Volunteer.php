<?php

namespace App\Models;

use App\Models\Scopes\CurrentlyScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ScopedBy(CurrentlyScope::class)]
class Volunteer extends Model
{
    use SoftDeletes;

    public $fillable = [
        'position_title',
        'started_at',
        'ended_at',
        'user_id',
        'organization_id',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }
}
